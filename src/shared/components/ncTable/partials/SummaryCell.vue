<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="summary-cell">
		<!-- Number columns: SUM -->
		<template v-if="isNumberColumn">
			<div class="number-summary">
				<span class="sum-label">Σ</span>
				<span class="sum-value">{{ formattedSum }}</span>
			</div>
		</template>

		<!-- Selection columns: Progress bar -->
		<template v-else-if="isSelectionColumn">
			<div class="selection-summary">
				<div class="progress-bar">
					<div
						v-for="stat in selectionStats"
						:key="stat.id"
						class="progress-segment"
						:style="{
							width: stat.percentage + '%',
							backgroundColor: stat.color
						}"
						:title="`${stat.label}: ${stat.count} (${stat.percentage}%)`" />
				</div>
				<div class="stats-tooltip">
					<span v-for="stat in selectionStats" :key="'label-' + stat.id" class="stat-item">
						<span class="stat-dot" :style="{ backgroundColor: stat.color }" />
						{{ stat.label }}: {{ stat.count }}
					</span>
				</div>
			</div>
		</template>

		<!-- Text columns: COUNT -->
		<template v-else-if="isTextColumn">
			<div class="count-summary">
				<span class="count-value">{{ rowCount }} {{ rowCount === 1 ? t('tablespro', 'item') : t('tablespro', 'items') }}</span>
			</div>
		</template>

		<!-- Other columns: empty -->
		<template v-else>
			<div class="empty-summary">-</div>
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { DEFAULT_OPTION_COLOR } from '../../../constants.ts'

export default {
	name: 'SummaryCell',

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

		isTextColumn() {
			return ['text-line', 'text-long', 'text-rich', 'text-link'].includes(this.column.type)
		},

		rowCount() {
			return this.rows.length
		},

		sum() {
			return this.rows.reduce((total, row) => {
				const cell = row.data?.find(c => c.columnId === this.column.id)
				const value = parseFloat(cell?.value) || 0
				return total + value
			}, 0)
		},

		formattedSum() {
			// Check if column has number settings for formatting
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

				// Handle multi-selection (array of values)
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
.summary-cell {
	padding: 4px 0;
	min-height: 24px;
}

.number-summary {
	display: flex;
	align-items: center;
	gap: 6px;
	font-weight: 600;

	.sum-label {
		color: var(--color-text-maxcontrast);
		font-size: 12px;
	}

	.sum-value {
		color: var(--color-main-text);
	}
}

.selection-summary {
	.progress-bar {
		display: flex;
		height: 8px;
		border-radius: 4px;
		overflow: hidden;
		background: var(--color-background-dark);
		margin-bottom: 4px;
	}

	.progress-segment {
		transition: width 0.3s ease;
	}

	.stats-tooltip {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		font-size: 11px;
		color: var(--color-text-maxcontrast);
	}

	.stat-item {
		display: flex;
		align-items: center;
		gap: 4px;
	}

	.stat-dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
		flex-shrink: 0;
	}
}

.count-summary {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.empty-summary {
	color: var(--color-text-maxcontrast);
}
</style>
