<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="collapsed-summary">
		<!-- Number columns: Show sum -->
		<template v-if="isNumberColumn && hasData">
			<span class="summary-value">{{ formattedSum }}</span>
			<span class="summary-label">{{ t('tablespro', 'sum') }}</span>
		</template>

		<!-- Selection columns: Mini progress bar -->
		<template v-else-if="isSelectionColumn && selectionStats.length > 0">
			<div class="mini-progress-bar">
				<div
					v-for="stat in selectionStats"
					:key="stat.id"
					class="progress-segment"
					:style="{
						width: stat.percentage + '%',
						backgroundColor: stat.color
					}"
					:title="`${stat.label}: ${stat.count}`" />
			</div>
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { DEFAULT_OPTION_COLOR } from '../../../constants.ts'

export default {
	name: 'CollapsedSummary',

	props: {
		column: {
			type: Object,
			required: true,
		},
		rows: {
			type: Array,
			default: () => [],
		},
	},

	computed: {
		isNumberColumn() {
			return ['number', 'number-stars', 'number-progress'].includes(this.column.type)
		},

		isSelectionColumn() {
			return ['selection', 'selection-multi'].includes(this.column.type)
		},

		hasData() {
			return this.rows.some(row => {
				const cell = row.data?.find(c => c.columnId === this.column.id)
				return cell?.value !== null && cell?.value !== undefined && cell?.value !== ''
			})
		},

		sum() {
			return this.rows.reduce((total, row) => {
				const cell = row.data?.find(c => c.columnId === this.column.id)
				const value = parseFloat(cell?.value) || 0
				return total + value
			}, 0)
		},

		formattedSum() {
			const prefix = this.column.numberPrefix || ''
			const suffix = this.column.numberSuffix || ''
			const decimals = this.column.numberDecimals ?? 2

			const formatted = new Intl.NumberFormat('sl-SI', {
				minimumFractionDigits: 0,
				maximumFractionDigits: decimals,
			}).format(this.sum)

			return `${prefix}${formatted}${suffix}`
		},

		selectionStats() {
			const counts = {}

			this.rows.forEach(row => {
				const cell = row.data?.find(c => c.columnId === this.column.id)
				const value = cell?.value

				if (Array.isArray(value)) {
					value.forEach(v => {
						if (v !== null && v !== undefined) {
							counts[v] = (counts[v] || 0) + 1
						}
					})
				} else if (value !== null && value !== undefined && value !== '') {
					counts[value] = (counts[value] || 0) + 1
				}
			})

			const options = this.column.selectionOptions || []
			return options
				.filter(opt => !opt.deleted)
				.map(opt => ({
					id: opt.id,
					label: opt.label,
					color: opt.color || DEFAULT_OPTION_COLOR,
					count: counts[opt.id] || 0,
					percentage: this.rows.length > 0
						? Math.round((counts[opt.id] || 0) / this.rows.length * 100)
						: 0,
				}))
				.filter(stat => stat.count > 0)
		},
	},

	methods: {
		t,
	},
}
</script>

<style lang="scss" scoped>
.collapsed-summary {
	display: flex;
	align-items: center;
	gap: 6px;
	white-space: nowrap;
}

.summary-value {
	font-weight: 600;
	font-size: 13px;
	color: var(--color-main-text);
}

.summary-label {
	font-size: 11px;
	color: var(--color-text-maxcontrast);
	text-transform: lowercase;
}

.mini-progress-bar {
	display: flex;
	height: 20px;
	min-width: 80px;
	max-width: 120px;
	border-radius: 4px;
	overflow: hidden;
	background: var(--color-background-dark);

	.progress-segment {
		height: 100%;
		transition: width 0.2s ease;
	}
}
</style>
