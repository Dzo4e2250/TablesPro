/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { DEFAULT_OPTION_COLOR } from '../../../constants.ts'

/**
 * Mixin for summary calculations
 */
export const summaryMixin = {
	data() {
		return {
			showSummary: true,
		}
	},

	computed: {
		/**
		 * Check if any column supports summary
		 */
		hasSummaryColumns() {
			return this.columns?.some(col =>
				['number', 'number-stars', 'number-progress', 'selection', 'selection-multi'].includes(col.type),
			)
		},
	},

	methods: {
		/**
		 * Calculate sum for a number column
		 */
		calculateSum(columnId, rows) {
			return rows.reduce((sum, row) => {
				const cell = row.data?.find(c => c.columnId === columnId)
				return sum + (parseFloat(cell?.value) || 0)
			}, 0)
		},

		/**
		 * Calculate average for a number column
		 */
		calculateAverage(columnId, rows) {
			if (rows.length === 0) return 0
			return this.calculateSum(columnId, rows) / rows.length
		},

		/**
		 * Calculate min for a number column
		 */
		calculateMin(columnId, rows) {
			const values = rows
				.map(row => {
					const cell = row.data?.find(c => c.columnId === columnId)
					return parseFloat(cell?.value)
				})
				.filter(v => !isNaN(v))

			return values.length > 0 ? Math.min(...values) : 0
		},

		/**
		 * Calculate max for a number column
		 */
		calculateMax(columnId, rows) {
			const values = rows
				.map(row => {
					const cell = row.data?.find(c => c.columnId === columnId)
					return parseFloat(cell?.value)
				})
				.filter(v => !isNaN(v))

			return values.length > 0 ? Math.max(...values) : 0
		},

		/**
		 * Calculate selection statistics
		 */
		calculateSelectionStats(column, rows) {
			const counts = {}

			rows.forEach(row => {
				const cell = row.data?.find(c => c.columnId === column.id)
				const value = cell?.value

				// Handle multi-selection
				if (Array.isArray(value)) {
					value.forEach(v => {
						if (v !== null && v !== undefined) {
							counts[v] = (counts[v] || 0) + 1
						}
					})
				} else if (value !== null && value !== undefined) {
					counts[value] = (counts[value] || 0) + 1
				}
			})

			const options = column.selectionOptions || []
			return options
				.filter(opt => !opt.deleted)
				.map(opt => ({
					id: opt.id,
					label: opt.label,
					color: opt.color || DEFAULT_OPTION_COLOR,
					count: counts[opt.id] || 0,
					percentage: rows.length > 0
						? Math.round((counts[opt.id] || 0) / rows.length * 100)
						: 0,
				}))
		},

		/**
		 * Check if column can have summary
		 */
		canHaveSummary(column) {
			return ['number', 'number-stars', 'number-progress', 'selection', 'selection-multi'].includes(column.type)
		},

		/**
		 * Toggle summary visibility
		 */
		toggleSummary() {
			this.showSummary = !this.showSummary
		},
	},
}
