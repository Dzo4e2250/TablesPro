<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr>
		<th v-if="config.canSelectRows" :class="{sticky: config.canSelectRows}">
			<div class="cell-wrapper">
				<NcCheckboxRadioSwitch :checked="allRowsAreSelected" @update:checked="value => $emit('select-all-rows', value)" />
				<div v-if="hasRightHiddenNeighbor(-1)" class="hidden-indicator-first" @click="unhide(-1)" />
			</div>
		</th>
		<th v-for="col in visibleColumns"
			:key="col.id"
			:ref="'col-' + col.id"
			:style="getColumnStyle(col)"
			class="resizable-column"
			:class="{ 'drag-over': dragOverColumnId === col.id, 'dragging': draggedColumnId === col.id }"
			@mouseenter="onColumnMouseEnter(col.id)"
			@mouseleave="onColumnMouseLeave">
			<div class="cell-wrapper">
				<div class="cell-options-wrapper">
					<div class="cell">
						<DragIcon
							class="drag-handle"
							:size="16"
							@mousedown.native.prevent="startColumnDrag($event, col)" />
						<div class="clickable" @click.stop="updateOpenState(col.id)">
							{{ col.title }}
						</div>
						<TableHeaderColumnOptions
							:column="col"
							:open-state.sync="openedColumnHeaderMenus[col.id]"
							:config="config"
							:view-setting.sync="localViewSetting"
							@edit-column="col => $emit('edit-column', col)"
							@delete-column="col => $emit('delete-column', col)" />
					</div>
					<div v-if="getFilterForColumn(col)" class="filter-wrapper">
						<FilterLabel v-for="filter in getFilterForColumn(col)"
							:id="filter.columnId + filter.operator.id+ filter.value"
							:key="filter.columnId + filter.operator.id+ filter.value"
							:operator="castToFilter(filter.operator.id)"
							:value="filter.value"
							@delete-filter="id => deleteFilter(id)" />
					</div>
				</div>
				<div v-if="hasRightHiddenNeighbor(col.id)" class="hidden-indicator" @click="unhide(col.id)" />
			</div>
			<div class="resize-handle" @mousedown.prevent="startResize($event, col)" />
		</th>
		<th v-if="config.showActions" data-cy="customTableAction" :class="{sticky: config.showActions}">
			<slot name="actions" />
		</th>
	</tr>
</template>

<script>
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import TableHeaderColumnOptions from './TableHeaderColumnOptions.vue'
import FilterLabel from './FilterLabel.vue'
import DragIcon from 'vue-material-design-icons/Drag.vue'
import { getFilterWithId } from '../mixins/filter.js'
import { getColumnWidthStyle } from '../mixins/columnHandler.js'

export default {

	components: {
		FilterLabel,
		NcCheckboxRadioSwitch,
		TableHeaderColumnOptions,
		DragIcon,
	},

	props: {
		columns: {
			type: Array,
			default: () => [],
		},
		rows: {
			type: Array,
			default: () => [],
		},
		selectedRows: {
			type: Array,
			default: () => [],
		},
		viewSetting: {
			type: Object,
			default: null,
		},
		config: {
			type: Object,
			default: null,
		},
	},

	data() {
		return {
			openedColumnHeaderMenus: {},
			localViewSetting: this.viewSetting,
			// Column resize state
			resizing: false,
			resizeColumnId: null,
			resizeStartX: 0,
			resizeStartWidth: 0,
			columnWidths: {},
			// Column drag state
			draggedColumnId: null,
			dragOverColumnId: null,
			hoveredColumnId: null,
			isDragging: false,
		}
	},

	computed: {
		allRowsAreSelected() {
			if (Array.isArray(this.rows) && Array.isArray(this.selectedRows) && this.rows.length !== 0) {
				return this.rows.length === this.selectedRows.length
			} else {
				return false
			}
		},
		visibleColumns() {
			return this.columns.filter(col => !this.localViewSetting?.hiddenColumns?.includes(col.id))
		},
	},
	watch: {
		localViewSetting: {
			deep: true,
			handler() {
				this.$emit('update:viewSetting', this.localViewSetting)
			},
		},
		viewSetting() {
			this.localViewSetting = this.viewSetting
			this.initColumnWidths()
		},
	},

	mounted() {
		// Initialize column widths from viewSetting or column customSettings
		this.initColumnWidths()
	},

	beforeDestroy() {
		// Clean up event listeners
		document.removeEventListener('mousemove', this.onResize)
		document.removeEventListener('mouseup', this.stopResize)
		document.removeEventListener('mousemove', this.onColumnDragMove)
		document.removeEventListener('mouseup', this.onColumnDragEnd)
	},

	methods: {
		getFilterWithId,
		getColumnWidthStyle,

		initColumnWidths() {
			const widths = {}
			// Load from localStorage first
			const storageKey = this.getStorageKey()
			const stored = storageKey ? localStorage.getItem(storageKey) : null
			const storedWidths = stored ? JSON.parse(stored) : {}

			this.columns.forEach(col => {
				// Priority: localStorage > viewSetting > customSettings > default
				if (storedWidths[col.id]) {
					widths[col.id] = storedWidths[col.id]
				} else if (this.localViewSetting?.columnWidths?.[col.id]) {
					widths[col.id] = this.localViewSetting.columnWidths[col.id]
				} else if (col.customSettings?.width) {
					widths[col.id] = col.customSettings.width
				}
			})
			this.columnWidths = widths
		},

		getStorageKey() {
			// Create a unique key based on the columns (table identifier)
			if (!this.columns || this.columns.length === 0) return null
			const tableId = this.columns[0]?.tableId || 'default'
			return `tablespro-column-widths-${tableId}`
		},

		saveColumnWidthsToStorage() {
			const storageKey = this.getStorageKey()
			if (storageKey) {
				localStorage.setItem(storageKey, JSON.stringify(this.columnWidths))
			}
		},

		getColumnStyle(col) {
			const width = this.columnWidths[col.id]
			if (width) {
				return {
					width: `${width}px`,
					minWidth: `${width}px`,
				}
			}
			return getColumnWidthStyle(col)
		},

		startResize(event, column) {
			this.resizing = true
			this.resizeColumnId = column.id

			// Get the th element
			const th = event.target.closest('th')
			this.resizeStartWidth = th.offsetWidth
			this.resizeStartX = event.clientX

			// Add listeners
			document.addEventListener('mousemove', this.onResize)
			document.addEventListener('mouseup', this.stopResize)

			// Add resizing class to body for cursor
			document.body.classList.add('column-resizing')
		},

		onResize(event) {
			if (!this.resizing) return

			const diff = event.clientX - this.resizeStartX
			const newWidth = Math.max(50, this.resizeStartWidth + diff) // Min width 50px

			this.$set(this.columnWidths, this.resizeColumnId, newWidth)
		},

		stopResize() {
			if (!this.resizing) return

			// Save the width to viewSetting
			if (!this.localViewSetting.columnWidths) {
				this.$set(this.localViewSetting, 'columnWidths', {})
			}
			this.$set(this.localViewSetting.columnWidths, this.resizeColumnId, this.columnWidths[this.resizeColumnId])

			// Save to localStorage for persistence
			this.saveColumnWidthsToStorage()

			this.resizing = false
			this.resizeColumnId = null

			// Remove listeners
			document.removeEventListener('mousemove', this.onResize)
			document.removeEventListener('mouseup', this.stopResize)

			// Remove resizing class
			document.body.classList.remove('column-resizing')
		},

		updateOpenState(columnId) {
			this.openedColumnHeaderMenus[columnId] = !this.openedColumnHeaderMenus[columnId]
			this.openedColumnHeaderMenus = Object.assign({}, this.openedColumnHeaderMenus)
		},
		getFilterForColumn(column) {
			return this.localViewSetting?.filter?.filter(item => item.columnId === column.id)
		},
		hasRightHiddenNeighbor(colId) {
			return this.localViewSetting?.hiddenColumns?.includes(this.columns[this.columns.indexOf(this.columns.find(col => col.id === colId)) + 1]?.id)
		},
		unhide(colId) {
			const index = this.localViewSetting.hiddenColumns.indexOf(this.columns[this.columns.indexOf(this.columns.find(col => col.id === colId)) + 1]?.id)
			if (index !== -1) {
				this.localViewSetting.hiddenColumns.splice(index, 1)
			}
		},
		deleteFilter(id) {
			const index = this.localViewSetting?.filter?.findIndex(item => item.columnId + item.operator.id + item.value === id)
			if (index !== -1) {
				this.localViewSetting.filter.splice(index, 1)
			}
		},
		castToFilter(operatorId) {
			return this.getFilterWithId(operatorId)
		},

		// Column drag & drop methods (mousedown-based for better compatibility)
		onColumnMouseEnter(columnId) {
			if (this.isDragging && this.draggedColumnId !== columnId) {
				this.dragOverColumnId = columnId
			}
			this.hoveredColumnId = columnId
		},

		onColumnMouseLeave() {
			if (!this.isDragging) {
				this.hoveredColumnId = null
			}
		},

		startColumnDrag(event, column) {
			this.draggedColumnId = column.id
			this.isDragging = true
			document.body.classList.add('column-dragging')

			// Add global listeners
			document.addEventListener('mousemove', this.onColumnDragMove)
			document.addEventListener('mouseup', this.onColumnDragEnd)
		},

		onColumnDragMove(event) {
			if (!this.isDragging) return

			// Find which column we're over
			const elements = document.elementsFromPoint(event.clientX, event.clientY)
			const th = elements.find(el => el.tagName === 'TH' && el.classList.contains('resizable-column'))

			if (th) {
				// Find the column id from ref
				const colId = this.visibleColumns.find(col => {
					const ref = this.$refs['col-' + col.id]
					return ref && ref[0] === th
				})?.id

				if (colId && colId !== this.draggedColumnId) {
					this.dragOverColumnId = colId
				}
			}
		},

		onColumnDragEnd() {
			document.removeEventListener('mousemove', this.onColumnDragMove)
			document.removeEventListener('mouseup', this.onColumnDragEnd)
			document.body.classList.remove('column-dragging')

			// If we have a valid drop target, emit reorder
			if (this.dragOverColumnId && this.draggedColumnId !== this.dragOverColumnId) {
				this.$emit('reorder-columns', {
					sourceColumnId: this.draggedColumnId,
					targetColumnId: this.dragOverColumnId,
				})
			}

			this.draggedColumnId = null
			this.dragOverColumnId = null
			this.isDragging = false
		},
	},
}
</script>
<style lang="scss" scoped>

th {
       white-space: normal;
}

.cell {
	display: inline-flex;
	align-items: center;
}

.cell span {
	padding-inline-start: 12px;

}

.filter-wrapper {
	margin-top: calc(var(--default-grid-baseline) * -1);
	margin-bottom: calc(var(--default-grid-baseline) * 2);
	display: flex;
	flex-wrap: wrap;
	gap: 0 calc(var(--default-grid-baseline) * 2);
}

:deep(.checkbox-radio-switch__icon) {
	margin: 0;
}

.clickable {
	cursor: pointer;
}

.hidden-indicator {
	border-inline-end: solid;
	border-color: var(--color-primary);
	border-width: 3px;
	padding-inline-start: calc(var(--default-grid-baseline) * 1);
	cursor: pointer;
}

.hidden-indicator-first {
	border-inline-end: solid;
	border-color: var(--color-primary);
	border-width: 3px;
	padding-inline-start: calc(var(--default-grid-baseline) * 4);
	cursor: pointer;
}

.cell-wrapper {
	display: flex;
	justify-content: space-between;
}

.cell-options-wrapper {
	display: flex;
	flex-direction: column;
	width: 100%;
}

// Column resize styles
.resizable-column {
	position: relative;
}

.resize-handle {
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	width: 6px;
	cursor: col-resize;
	background: transparent;
	transition: background-color 0.15s ease;
	z-index: 10;

	&:hover {
		background-color: var(--color-primary-element);
	}
}

// Global style for resize cursor
:global(body.column-resizing) {
	cursor: col-resize !important;
	user-select: none !important;
}

:global(body.column-resizing *) {
	cursor: col-resize !important;
}

// Column drag & drop styles
.drag-handle {
	cursor: grab;
	color: var(--color-text-maxcontrast);
	flex-shrink: 0;
	margin-inline-end: 8px;
	padding: 4px;
	border-radius: var(--border-radius);
	transition: all 0.15s ease;

	&:hover {
		color: var(--color-primary-element);
		background-color: var(--color-background-hover);
	}

	&:active {
		cursor: grabbing;
		color: var(--color-primary-element);
		background-color: var(--color-primary-element-light);
	}
}

th.dragging {
	opacity: 0.4;
	background-color: var(--color-background-dark) !important;
}

th.drag-over {
	background-color: var(--color-primary-element-light) !important;
	box-shadow: inset 0 0 0 2px var(--color-primary-element);
}

// Global style for drag cursor
:global(body.column-dragging) {
	cursor: grabbing !important;
	user-select: none !important;
}

:global(body.column-dragging *) {
	cursor: grabbing !important;
}

</style>
