/**
 * EasyAdmin Tree Select Field JavaScript - 下拉式版本
 */
(function() {
    'use strict';

    class TreeSelect {
        constructor(container) {
            this.container = container;
            this.isMultiple = container.dataset.treeSelect === 'multiple';
            this.isSearchable = container.dataset.searchable === 'true';
            this.isSortable = container.dataset.sortable === 'true';
            this.isLazyLoad = container.dataset.lazyLoad === 'true';
            this.isOpen = false;
            
            // 记录初始选择状态，用于保护历史数据
            this.initialSelectedValues = new Set();
            this.initialSelectedValue = null;
            this.initialSelectedLabels = new Map();
            this.initialSelectedLabel = null;
            
            // 从choices建立ID到名称的映射
            this.choiceLabels = new Map();
            
            // DOM 元素
            this.originalSelect = container.querySelector('select');
            this.trigger = container.querySelector('[data-tree-trigger]');
            this.dropdown = container.querySelector('[data-tree-dropdown]');
            this.overlay = container.querySelector('[data-dropdown-overlay]');
            this.treeContainer = container.querySelector('.tree-container');
            this.searchInput = container.querySelector('[data-tree-search]');
            this.selectedDisplay = container.querySelector('[data-selected-display]');
            this.singleDisplay = container.querySelector('[data-single-display]');
            this.placeholder = container.querySelector('.placeholder');
            
            // 从数据属性读取树数据
            const treeContent = container.querySelector('.tree-content');
            if (treeContent && treeContent.dataset.treeData) {
                try {
                    this.treeData = JSON.parse(treeContent.dataset.treeData);
                } catch (e) {
                    console.error('Failed to parse tree data:', e);
                    this.treeData = [];
                }
            }
            
            this.init();
        }

        init() {
            this.buildChoiceLabelsMap();
            this.bindEvents();
            this.syncInitialSelections();
            this.updateTriggerDisplay();
            this.setupAccessibility();
            
            if (this.isSortable) {
                this.initSortable();
            }
        }

        /**
         * 从原始select的选项中构建ID到标签的映射，同时从树形结构中获取标签
         */
        buildChoiceLabelsMap() {
            if (!this.originalSelect) return;
            
            // 从所有option构建映射，包括choices和历史数据
            Array.from(this.originalSelect.options).forEach(option => {
                if (option.value && option.textContent) {
                    this.choiceLabels.set(option.value, option.textContent);
                }
            });
            
            // 从树形结构中获取所有节点的ID到标签映射
            const treeNodes = this.treeContainer.querySelectorAll('[data-node-id]');
            treeNodes.forEach(node => {
                const nodeId = node.dataset.nodeId;
                const nodeLabel = node.dataset.nodeLabel;
                if (nodeId && nodeLabel) {
                    this.choiceLabels.set(nodeId, nodeLabel);
                }
            });
        }

        bindEvents() {
            // 触发器点击事件
            this.trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });

            // 触发器键盘事件
            this.trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggle();
                } else if (e.key === 'Escape') {
                    this.close();
                }
            });

            // 遮罩层点击关闭
            this.overlay.addEventListener('click', () => {
                this.close();
            });

            // 搜索功能
            if (this.searchInput) {
                let searchTimeout;
                this.searchInput.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.handleSearch(e.target.value);
                    }, 300);
                });
                
                // 防止搜索框点击时关闭下拉框
                this.searchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            // 树节点展开/收起
            this.treeContainer.addEventListener('click', (e) => {
                if (e.target.matches('.tree-toggle, .tree-toggle *')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleNode(e.target.closest('.tree-node'));
                }
            });

            // 复选框/单选框变化
            this.treeContainer.addEventListener('change', (e) => {
                if (e.target.matches('.tree-checkbox, .tree-radio')) {
                    this.handleSelectionChange(e.target);
                    // 单选时自动关闭下拉框
                    if (!this.isMultiple) {
                        setTimeout(() => this.close(), 200);
                    }
                }
            });

            // 全部展开/收起按钮
            const expandAllBtn = this.container.querySelector('[data-expand-all]');
            const collapseAllBtn = this.container.querySelector('[data-collapse-all]');
            
            if (expandAllBtn) {
                expandAllBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.expandAll();
                });
            }
            
            if (collapseAllBtn) {
                collapseAllBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.collapseAll();
                });
            }

            // 清空所有选择
            const clearAllBtn = this.container.querySelector('[data-clear-all]');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.clearAllSelections();
                });
            }

            // 全局键盘事件
            document.addEventListener('keydown', (e) => {
                if (this.isOpen && e.key === 'Escape') {
                    this.close();
                }
            });

            // 点击外部关闭
            document.addEventListener('click', (e) => {
                if (this.isOpen && !this.container.contains(e.target)) {
                    this.close();
                }
            });

            // 删除标签事件委托
            this.trigger.addEventListener('click', (e) => {
                if (e.target.matches('.tag-remove, .tag-remove *')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const tag = e.target.closest('.selected-tag');
                    if (tag) {
                        const nodeId = tag.dataset.nodeId;
                        this.removeSelection(nodeId);
                    }
                }
            });
        }

        setupAccessibility() {
            // 设置 ARIA 属性
            this.trigger.setAttribute('role', 'combobox');
            this.trigger.setAttribute('aria-expanded', 'false');
            this.trigger.setAttribute('aria-haspopup', 'tree');
            
            const dropdownId = 'tree-dropdown-' + Math.random().toString(36).substring(7);
            this.dropdown.id = dropdownId;
            this.trigger.setAttribute('aria-controls', dropdownId);
        }

        /**
         * 同步初始选择状态 - 修复编辑时数据丢失问题
         * 从原始select元素的选中状态同步到树形checkbox
         */
        syncInitialSelections() {
            if (!this.originalSelect) return;
            
            if (this.isMultiple) {
                // 多选：获取所有selected的option，包括历史数据
                const selectedOptions = Array.from(this.originalSelect.options).filter(option => option.selected);
                const selectedValues = selectedOptions.map(option => option.value);
                
                // 记录初始选择的值和标签，包括在树中找不到的历史数据
                this.initialSelectedValues = new Set(selectedValues);
                this.initialSelectedLabels = new Map();
                
                selectedOptions.forEach(option => {
                    // 确保存储正确的标签文本，不要存储"分类 + ID"的格式
                    let labelText = option.textContent || option.value;
                    // 如果是"分类 xxxx"格式，只保留分类名部分
                    if (labelText && labelText.startsWith('分类 ') && labelText !== '分类 ' + option.value) {
                        labelText = labelText.replace(/^分类 /, '');
                    }
                    this.initialSelectedLabels.set(option.value, labelText);
                });
                
                // 同步到树形checkbox
                selectedValues.forEach(value => {
                    const checkbox = this.treeContainer.querySelector(`[data-node-id="${value}"].tree-checkbox`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            } else {
                // 单选：获取选中的值
                const selectedValue = this.originalSelect.value;
                if (selectedValue) {
                    this.initialSelectedValue = selectedValue;
                    const selectedOption = this.originalSelect.querySelector('option:checked');
                    let labelText = selectedOption ? 
                        (selectedOption.textContent || selectedValue) : selectedValue;
                    
                    // 如果是"分类 xxxx"格式，只保留分类名部分
                    if (labelText && labelText.startsWith('分类 ') && labelText !== '分类 ' + selectedValue) {
                        labelText = labelText.replace(/^分类 /, '');
                    }
                    
                    this.initialSelectedLabel = labelText;
                    
                    const radio = this.treeContainer.querySelector(`[data-node-id="${selectedValue}"].tree-radio`);
                    if (radio) {
                        radio.checked = true;
                    }
                }
            }
        }

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        open() {
            if (this.isOpen) return;
            
            this.isOpen = true;
            this.dropdown.style.display = 'block';
            this.overlay.style.display = 'block';
            
            // 更新箭头方向
            const arrow = this.trigger.querySelector('.trigger-arrow i');
            if (arrow) {
                arrow.classList.remove('fa-chevron-down');
                arrow.classList.add('fa-chevron-up');
            }
            
            // 更新 ARIA 属性
            this.trigger.setAttribute('aria-expanded', 'true');
            
            // 动画效果
            requestAnimationFrame(() => {
                this.dropdown.classList.add('show');
                this.overlay.classList.add('show');
            });
            
            // 自动聚焦搜索框
            if (this.searchInput) {
                setTimeout(() => {
                    this.searchInput.focus();
                }, 100);
            }
            
            this.positionDropdown();
        }

        close() {
            if (!this.isOpen) return;
            
            this.isOpen = false;
            
            // 更新箭头方向
            const arrow = this.trigger.querySelector('.trigger-arrow i');
            if (arrow) {
                arrow.classList.remove('fa-chevron-up');
                arrow.classList.add('fa-chevron-down');
            }
            
            // 更新 ARIA 属性
            this.trigger.setAttribute('aria-expanded', 'false');
            
            // 动画效果
            this.dropdown.classList.remove('show');
            this.overlay.classList.remove('show');
            
            setTimeout(() => {
                this.dropdown.style.display = 'none';
                this.overlay.style.display = 'none';
            }, 200);
            
            // 清空搜索
            if (this.searchInput) {
                this.searchInput.value = '';
                this.handleSearch('');
            }
        }

        positionDropdown() {
            const triggerRect = this.trigger.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;
            
            // 计算实际内容高度
            this.dropdown.style.visibility = 'hidden';
            this.dropdown.style.display = 'block';
            this.dropdown.style.height = 'auto';
            
            const dropdownRect = this.dropdown.getBoundingClientRect();
            const actualHeight = Math.min(dropdownRect.height, viewportHeight * 0.8);
            
            // 计算可用空间
            const spaceBelow = viewportHeight - triggerRect.bottom - 20; // 留20px边距
            const spaceAbove = triggerRect.top - 20; // 留20px边距
            
            // 决定显示位置
            if (spaceBelow >= actualHeight || spaceBelow > spaceAbove) {
                // 在下方显示
                this.dropdown.style.top = '100%';
                this.dropdown.style.bottom = 'auto';
                this.dropdown.style.maxHeight = `${Math.min(actualHeight, spaceBelow)}px`;
                this.dropdown.classList.remove('dropdown-up');
            } else {
                // 在上方显示
                this.dropdown.style.top = 'auto';
                this.dropdown.style.bottom = '100%';
                this.dropdown.style.maxHeight = `${Math.min(actualHeight, spaceAbove)}px`;
                this.dropdown.classList.add('dropdown-up');
            }
            
            // 处理水平方向的边界
            const dropdownWidth = this.dropdown.offsetWidth;
            const leftOffset = triggerRect.left;
            const rightOffset = viewportWidth - triggerRect.right;
            
            if (leftOffset < 0) {
                this.dropdown.style.left = `${-leftOffset + 10}px`;
            } else if (rightOffset < 0) {
                this.dropdown.style.right = `${-rightOffset + 10}px`;
                this.dropdown.style.left = 'auto';
            }
            
            // 添加动态高度类
            this.dropdown.classList.add('dynamic-height');
            
            // 恢复可见性
            this.dropdown.style.visibility = 'visible';
        }

        handleSearch(query) {
            const nodes = this.treeContainer.querySelectorAll('.tree-node');
            
            if (!query.trim()) {
                nodes.forEach(node => {
                    node.style.display = '';
                    node.classList.remove('search-matched', 'search-hidden');
                });
                return;
            }

            const lowerQuery = query.toLowerCase();
            
            nodes.forEach(node => {
                const label = node.querySelector('.tree-label');
                const text = label ? label.textContent.toLowerCase() : '';
                
                if (text.includes(lowerQuery)) {
                    node.style.display = '';
                    node.classList.add('search-matched');
                    node.classList.remove('search-hidden');
                    this.expandParents(node);
                } else {
                    node.style.display = 'none';
                    node.classList.add('search-hidden');
                    node.classList.remove('search-matched');
                }
            });
        }

        expandParents(node) {
            let parent = node.closest('.tree-children');
            while (parent) {
                parent.classList.add('show');
                parent.classList.remove('collapse');
                
                const parentNode = parent.closest('.tree-node');
                if (parentNode) {
                    const toggleIcon = parentNode.querySelector('.tree-toggle-icon');
                    if (toggleIcon) {
                        toggleIcon.classList.remove('fa-chevron-right');
                        toggleIcon.classList.add('fa-chevron-down');
                    }
                    parent = parentNode.parentElement.closest('.tree-children');
                } else {
                    parent = null;
                }
            }
        }

        toggleNode(node) {
            const childrenContainer = node.querySelector('.tree-children');
            const toggleIcon = node.querySelector('.tree-toggle-icon');
            
            if (!childrenContainer || !toggleIcon) return;

            const isExpanding = !childrenContainer.classList.contains('show');

            if (childrenContainer.classList.contains('show')) {
                childrenContainer.classList.remove('show');
                childrenContainer.classList.add('collapse');
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-right');
            } else {
                childrenContainer.classList.add('show');
                childrenContainer.classList.remove('collapse');
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-down');
                
                // 展开后确保子节点可见
                if (isExpanding) {
                    setTimeout(() => {
                        this.scrollToExpandedNode(node, childrenContainer);
                    }, 100); // 等待CSS过渡完成
                }
            }
        }

        /**
         * 智能滚动到展开的节点，确保子节点可见
         */
        scrollToExpandedNode(parentNode, childrenContainer) {
            if (!this.isOpen || !childrenContainer) return;
            
            // 获取容器和第一个子节点
            const container = this.treeContainer;
            const firstChild = childrenContainer.querySelector('.tree-node');
            
            if (!firstChild) return;
            
            // 计算容器和节点的位置
            const containerRect = container.getBoundingClientRect();
            const childRect = firstChild.getBoundingClientRect();
            const parentRect = parentNode.getBoundingClientRect();
            
            // 检查子节点是否在可视区域内
            const containerTop = containerRect.top;
            const containerBottom = containerRect.bottom;
            const nodeTop = childRect.top;
            const nodeBottom = childRect.bottom;
            
            // 如果子节点不完全可见，则滚动
            if (nodeTop < containerTop || nodeBottom > containerBottom) {
                // 计算滚动位置
                const scrollOffset = container.scrollTop;
                const containerHeight = container.offsetHeight;
                const nodeOffsetTop = firstChild.offsetTop;
                const parentOffsetTop = parentNode.offsetTop;
                
                let targetScrollTop;
                
                if (nodeTop < containerTop) {
                    // 子节点在视口上方，滚动到顶部
                    targetScrollTop = nodeOffsetTop - 20; // 留20px边距
                } else {
                    // 子节点在视口下方，滚动使其可见
                    const nodeHeight = firstChild.offsetHeight;
                    const visibleChildren = childrenContainer.querySelectorAll('.tree-node').length;
                    const estimatedTotalHeight = nodeHeight * Math.min(visibleChildren, 3); // 最多显示3个子节点
                    
                    targetScrollTop = nodeOffsetTop - (containerHeight - estimatedTotalHeight - 40);
                }
                
                // 确保滚动位置在有效范围内
                targetScrollTop = Math.max(0, targetScrollTop);
                targetScrollTop = Math.min(targetScrollTop, container.scrollHeight - containerHeight);
                
                // 平滑滚动
                container.scrollTo({
                    top: targetScrollTop,
                    behavior: 'smooth'
                });
                
                // 添加视觉标记
                container.classList.add('expanded-visible');
                setTimeout(() => {
                    container.classList.remove('expanded-visible');
                }, 2000);
            }
        }

        expandAll() {
            const childrenContainers = this.treeContainer.querySelectorAll('.tree-children');
            const toggleIcons = this.treeContainer.querySelectorAll('.tree-toggle-icon');
            
            childrenContainers.forEach(container => {
                container.classList.add('show');
                container.classList.remove('collapse');
            });
            
            toggleIcons.forEach(icon => {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            });
        }

        collapseAll() {
            const childrenContainers = this.treeContainer.querySelectorAll('.tree-children');
            const toggleIcons = this.treeContainer.querySelectorAll('.tree-toggle-icon');
            
            childrenContainers.forEach(container => {
                container.classList.remove('show');
                container.classList.add('collapse');
            });
            
            toggleIcons.forEach(icon => {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            });
        }

        handleSelectionChange(input) {
            const nodeId = input.dataset.nodeId;
            const nodeLabel = input.dataset.nodeLabel;

            if (this.isMultiple) {
                if (input.checked) {
                    this.addToOriginalSelect(nodeId);
                } else {
                    this.removeFromOriginalSelect(nodeId);
                }
            } else {
                if (input.checked) {
                    const otherRadios = this.treeContainer.querySelectorAll('.tree-radio');
                    otherRadios.forEach(radio => {
                        if (radio !== input) {
                            radio.checked = false;
                        }
                    });
                    
                    // 更新原始select的值
                    this.originalSelect.value = nodeId;
                    
                    // 确保选中的option有正确的文本内容
                    let selectedOption = this.originalSelect.querySelector(`option[value="${nodeId}"]`);
                    if (!selectedOption) {
                        selectedOption = document.createElement('option');
                        selectedOption.value = nodeId;
                        this.originalSelect.appendChild(selectedOption);
                    }
                    selectedOption.textContent = nodeLabel;
                    selectedOption.selected = true;
                    
                    // 更新标签映射
                    this.choiceLabels.set(nodeId, nodeLabel);
                }
            }

            this.updateTriggerDisplay();
            this.triggerChangeEvent();
        }

        addToOriginalSelect(value) {
            if (!this.isMultiple) return;

            const option = this.originalSelect.querySelector(`option[value="${value}"]`);
            if (option) {
                option.selected = true;
            } else {
                // 创建新的option，设置正确的文本内容
                const newOption = document.createElement('option');
                newOption.value = value;
                newOption.selected = true;
                
                // 从choiceLabels映射中获取标签，如果没有则从树形结构获取
                let label = this.choiceLabels.get(value);
                if (!label) {
                    const treeInput = this.treeContainer.querySelector(`[data-node-id="${value}"]`);
                    label = treeInput ? treeInput.dataset.nodeLabel : value;
                    // 更新映射
                    this.choiceLabels.set(value, label);
                }
                
                newOption.textContent = label;
                this.originalSelect.appendChild(newOption);
            }
        }

        removeFromOriginalSelect(value) {
            if (!this.isMultiple) return;

            const option = this.originalSelect.querySelector(`option[value="${value}"]`);
            if (option) {
                option.selected = false;
                // 不删除option，只是取消选中，保持数据完整性
            }
        }

        removeSelection(nodeId) {
            // 从树形checkbox中移除（如果存在）
            const checkbox = this.treeContainer.querySelector(`[data-node-id="${nodeId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            
            // 从初始选择记录中移除
            if (this.isMultiple) {
                this.initialSelectedValues.delete(nodeId);
            } else {
                if (this.initialSelectedValue === nodeId) {
                    this.initialSelectedValue = null;
                }
            }
            
            // 从原始select中移除
            this.removeFromOriginalSelect(nodeId);
            this.updateTriggerDisplay();
            this.triggerChangeEvent();
        }

        clearAllSelections() {
            const inputs = this.treeContainer.querySelectorAll('.tree-checkbox, .tree-radio');
            inputs.forEach(input => {
                input.checked = false;
            });

            // 清除初始选择记录
            if (this.isMultiple) {
                this.initialSelectedValues.clear();
                // 清空所有选中状态
                Array.from(this.originalSelect.options).forEach(option => {
                    option.selected = false;
                });
            } else {
                this.initialSelectedValue = null;
                this.originalSelect.value = '';
            }

            this.updateTriggerDisplay();
            this.triggerChangeEvent();
        }

        updateTriggerDisplay() {
            if (this.isMultiple) {
                this.updateMultipleDisplay();
            } else {
                this.updateSingleDisplay();
            }
        }

        updateMultipleDisplay() {
            const selectedTagsContainer = this.selectedDisplay;
            const placeholder = this.placeholder;
            
            if (!selectedTagsContainer) return;
            
            // 获取所有已选择的选项，确保包含历史数据
            const selectedMap = new Map();
            
            // 1. 首先从原始select中获取所有选中的option，包括历史数据
            const selectedOptions = Array.from(this.originalSelect.options).filter(option => option.selected);
            selectedOptions.forEach(option => {
                const value = option.value;
                const isHistorical = option.hasAttribute('data-historical');
                
                // 获取显示标签 - 优先级：choiceLabels映射 > 初始标签 > option文本 > 树形结构 > ID
                let nodeLabel = this.choiceLabels.get(value) || 
                               this.initialSelectedLabels?.get(value) || 
                               option.textContent || option.value;
                
                // 如果仍然没有合适的标签或者标签就是ID本身，尝试从树形结构中获取
                if (!nodeLabel || nodeLabel === value) {
                    const treeInput = this.treeContainer.querySelector(`[data-node-id="${value}"]`);
                    nodeLabel = treeInput ? treeInput.dataset.nodeLabel : value;
                }
                
                selectedMap.set(value, { label: nodeLabel, isHistorical });
            });
            
            // 2. 然后添加当前树形checkbox中选中的项（可能是新选择的）
            const checkedInputs = this.treeContainer.querySelectorAll('.tree-checkbox:checked');
            checkedInputs.forEach(input => {
                const nodeId = input.dataset.nodeId;
                const nodeLabel = input.dataset.nodeLabel;
                
                // 确保对应的option被选中（这会创建新option或更新现有option）
                this.addToOriginalSelect(nodeId);
                
                // 如果不在selectedMap中，说明是新选中的，或者更新现有的
                selectedMap.set(nodeId, { label: nodeLabel, isHistorical: false });
            });
            
            // 3. 移除取消选择的项
            const allSelectedValues = new Set([...selectedMap.keys()]);
            Array.from(this.originalSelect.options).forEach(option => {
                if (!allSelectedValues.has(option.value)) {
                    option.selected = false;
                }
            });
            
            // 4. 更新显示
            selectedTagsContainer.innerHTML = '';
            
            if (selectedMap.size > 0) {
                placeholder.style.display = 'none';
                
                selectedMap.forEach(({ label: nodeLabel, isHistorical }, nodeId) => {
                    const tag = document.createElement('span');
                    tag.className = 'selected-tag';
                    tag.dataset.nodeId = nodeId;
                    
                    // 标记历史数据
                    if (isHistorical || !this.treeContainer.querySelector(`[data-node-id="${nodeId}"]`)) {
                        tag.classList.add('historical-tag');
                        tag.title = '历史分类（不在当前分类树中）';
                    }
                    
                    tag.innerHTML = `
                        <span class="tag-label">${nodeLabel}</span>
                        <i class="fas fa-times tag-remove"></i>
                    `;
                    
                    selectedTagsContainer.appendChild(tag);
                });
            } else {
                placeholder.style.display = '';
            }
        }

        updateSingleDisplay() {
            const singleDisplay = this.singleDisplay;
            const placeholder = this.placeholder;
            
            if (!singleDisplay) return;
            
            // 优先从原始select获取选中值，确保编辑时数据不丢失
            const selectedValue = this.originalSelect.value;
            let nodeLabel = '';
            let isHistorical = false;
            
            if (selectedValue) {
                // 检查是否为历史数据
                const selectedOption = this.originalSelect.querySelector(`option[value="${selectedValue}"]:checked`);
                isHistorical = selectedOption && selectedOption.hasAttribute('data-historical');
                
                // 获取显示标签 - 优先级：choiceLabels映射 > 初始标签 > option文本 > 树形结构 > ID
                nodeLabel = this.choiceLabels.get(selectedValue) || 
                           this.initialSelectedLabel || 
                           (selectedOption ? selectedOption.textContent : null);
                
                // 如果没有标签，尝试从树形结构中获取
                if (!nodeLabel) {
                    const treeInput = this.treeContainer.querySelector(`[data-node-id="${selectedValue}"]`);
                    nodeLabel = treeInput ? treeInput.dataset.nodeLabel : selectedValue;
                }
            } else {
                // 如果原始select没有值，检查树形radio
                const checkedInput = this.treeContainer.querySelector('.tree-radio:checked');
                if (checkedInput) {
                    nodeLabel = checkedInput.dataset.nodeLabel;
                    this.originalSelect.value = checkedInput.dataset.nodeId;
                }
            }
            
            if (nodeLabel) {
                singleDisplay.textContent = nodeLabel;
                singleDisplay.style.display = '';
                placeholder.style.display = 'none';
                
                // 为历史数据添加视觉提示
                if (isHistorical || !this.treeContainer.querySelector(`[data-node-id="${selectedValue}"]`)) {
                    singleDisplay.classList.add('historical-value');
                    singleDisplay.title = '历史分类（不在当前分类树中）';
                } else {
                    singleDisplay.classList.remove('historical-value');
                    singleDisplay.removeAttribute('title');
                }
            } else {
                singleDisplay.style.display = 'none';
                placeholder.style.display = '';
                singleDisplay.classList.remove('historical-value');
                singleDisplay.removeAttribute('title');
            }
        }

        triggerChangeEvent() {
            const event = new Event('change', { bubbles: true });
            this.originalSelect.dispatchEvent(event);
        }

        initSortable() {
            // 排序功能预留
        }
    }

    // 初始化所有树状选择器
    function initTreeSelect() {
        const containers = document.querySelectorAll('.tree-select-widget');
        containers.forEach(container => {
            if (!container.treeSelectInstance) {
                container.treeSelectInstance = new TreeSelect(container);
            }
        });
    }

    // DOM 加载完成时初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTreeSelect);
    } else {
        initTreeSelect();
    }

    // 支持动态添加的表单字段
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const containers = node.querySelectorAll ? 
                            node.querySelectorAll('.tree-select-widget') : [];
                        containers.forEach(container => {
                            if (!container.treeSelectInstance) {
                                container.treeSelectInstance = new TreeSelect(container);
                            }
                        });
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // 导出到全局
    window.TreeSelect = TreeSelect;

})();