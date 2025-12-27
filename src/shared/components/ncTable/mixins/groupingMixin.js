/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Mixin for row grouping functionality
 */
export const groupingMixin = {
	data() {
		return {
			groupByColumnId: null,
			expandedGroups: {},
		}
	},

	computed: {
		/**
		 * Get the column we're grouping by
		 */
		groupByColumn() {
			if (!this.groupByColumnId) return null
			return this.columns?.find(col => col.id === this.groupByColumnId)
		},

		/**
		 * Check if grouping is enabled
		 */
		isGrouped() {
			return this.groupByColumnId !== null
		},

		/**
		 * Get rows organized into groups
		 */
		groupedRows() {
			if (!this.groupByColumnId) return null

			const groups = {}
			const ungroupedKey = '__ungrouped__'

			this.getSearchedAndFilteredAndSortedRows.forEach(row => {
				const cell = row.data?.find(c => c.columnId === this.groupByColumnId)
				let groupValue = cell?.value

				// Handle null/undefined values
				if (groupValue === null || groupValue === undefined || groupValue === '') {
					groupValue = ungroupedKey
				}

				// Create group if doesn't exist
				if (!groups[groupValue]) {
					groups[groupValue] = {
						value: groupValue,
						label: this.getGroupLabel(groupValue),
						color: this.getGroupColor(groupValue),
						rows: [],
						expanded: this.expandedGroups[groupValue] !== false,
					}
				}

				groups[groupValue].rows.push(row)
			})

			// Sort groups by label, ungrouped last
			return Object.values(groups).sort((a, b) => {
				if (a.value === ungroupedKey) return 1
				if (b.value === ungroupedKey) return -1
				return String(a.label).localeCompare(String(b.label))
			})
		},

		/**
		 * Get columns that can be used for grouping
		 */
		groupableColumns() {
			return this.columns?.filter(col =>
				['selection', 'text-line'].includes(col.type),
			) || []
		},

		/**
		 * Check if there are groupable columns
		 */
		hasGroupableColumns() {
			return this.groupableColumns.length > 0
		},
	},

	methods: {
		/**
		 * Get display label for a group value
		 */
		getGroupLabel(groupValue) {
			if (groupValue === '__ungrouped__') {
				return t('tablespro', 'Ungrouped')
			}

			// If grouping by selection column, get the option label
			if (this.groupByColumn?.type === 'selection') {
				const option = this.groupByColumn.selectionOptions?.find(
					opt => opt.id === groupValue,
				)
				return option?.label || String(groupValue)
			}

			return String(groupValue)
		},

		/**
		 * Get color for a group (if selection column)
		 */
		getGroupColor(groupValue) {
			if (this.groupByColumn?.type === 'selection') {
				const option = this.groupByColumn.selectionOptions?.find(
					opt => opt.id === groupValue,
				)
				return option?.color || null
			}
			return null
		},

		/**
		 * Toggle group expanded/collapsed state
		 */
		toggleGroup(groupValue) {
			const currentState = this.expandedGroups[groupValue]
			this.$set(this.expandedGroups, groupValue, currentState === false)
		},

		/**
		 * Expand all groups
		 */
		expandAllGroups() {
			if (!this.groupedRows) return
			this.groupedRows.forEach(group => {
				this.$set(this.expandedGroups, group.value, true)
			})
		},

		/**
		 * Collapse all groups
		 */
		collapseAllGroups() {
			if (!this.groupedRows) return
			this.groupedRows.forEach(group => {
				this.$set(this.expandedGroups, group.value, false)
			})
		},

		/**
		 * Set the column to group by
		 */
		setGroupBy(columnId) {
			this.groupByColumnId = columnId
			this.expandedGroups = {}
		},

		/**
		 * Clear grouping
		 */
		clearGrouping() {
			this.groupByColumnId = null
			this.expandedGroups = {}
		},

		/**
		 * Add item to a specific group
		 */
		addItemToGroup(groupValue) {
			if (groupValue === '__ungrouped__') {
				this.$emit('create-row')
				return
			}

			this.$emit('create-row', {
				prefill: {
					[this.groupByColumnId]: groupValue,
				},
			})
		},
	},
}
