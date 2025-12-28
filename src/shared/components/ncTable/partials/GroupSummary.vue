<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr class="group-summary">
		<td class="sticky group-summary-label">
			<span class="item-count">{{ rows.length }}</span>
		</td>
		<td v-for="column in columns" :key="'gsummary-' + column.id" class="group-summary-cell" :style="getColumnStyle(column)">
			<SummaryCell v-if="hasSummary(column)" :column="column" :rows="rows" />
		</td>
		<td class="sticky-end" />
	</tr>
</template>

<script>
import SummaryCell from './SummaryCell.vue'
import { getColumnWidthStyle } from '../mixins/columnHandler.js'

export default {
	name: 'GroupSummary',

	components: {
		SummaryCell,
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
		viewSetting: {
			type: Object,
			default: null,
		},
	},

	methods: {
		hasSummary(column) {
			// Show summary for number columns and selection columns
			return ['number', 'number-stars', 'number-progress', 'selection', 'selection-multi'].includes(column.type)
		},
		getColumnStyle(col) {
			// Check localStorage for resized columns
			const tableId = col.tableId || 'default'
			const storageKey = `tablespro-column-widths-${tableId}`
			const stored = localStorage.getItem(storageKey)
			if (stored) {
				const widths = JSON.parse(stored)
				if (widths[col.id]) {
					return {
						width: `${widths[col.id]}px`,
						minWidth: `${widths[col.id]}px`,
					}
				}
			}
			return getColumnWidthStyle(col)
		},
	},
}
</script>

<style lang="scss" scoped>
.group-summary {
	background-color: var(--color-background-dark) !important;

	td {
		padding: 2px 6px !important;
		border-top: 1px solid var(--color-border-dark) !important;
		border-bottom: none !important;
		border-inline-end: 1px solid var(--color-border) !important;
		vertical-align: middle;
	}

	.group-summary-label {
		position: sticky;
		inset-inline-start: 0;
		z-index: 5;
		background-color: var(--color-background-dark);
		border-inline-end: none !important;
	}

	.item-count {
		font-size: 11px;
		color: var(--color-text-maxcontrast);
		font-weight: 500;
	}

	.sticky-end {
		position: sticky;
		inset-inline-end: 0;
		background-color: var(--color-background-dark);
		border-inline-end: none !important;
	}
}
</style>
