<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="summary-cell" :class="{ 'has-dropdown': isNumberColumn }">
		<!-- Number columns: Aggregation with dropdown -->
		<template v-if="isNumberColumn">
			<div ref="triggerRef" class="number-summary" @click.stop="toggleDropdown">
				<span class="func-label">{{ currentFunctionLabel }}</span>
				<span class="sum-value">{{ formattedValue }}</span>
				<ChevronDownIcon class="dropdown-icon" :size="14" />
			</div>

			<!-- Dropdown menu - position fixed to escape stacking context -->
			<div
				v-if="showDropdown"
				v-click-outside="closeDropdown"
				class="aggregation-dropdown"
				:style="dropdownStyle">
				<button
					v-for="func in aggregationFunctions"
					:key="func.id"
					class="dropdown-item"
					:class="{ active: selectedFunction === func.id }"
					@click.stop="selectFunction(func.id)">
					<span class="func-symbol">{{ func.symbol }}</span>
					<span class="func-name">{{ func.name }}</span>
					<CheckIcon v-if="selectedFunction === func.id" class="check-icon" :size="16" />
				</button>
			</div>
		</template>

		<!-- Selection columns: Full cell color bar -->
		<template v-else-if="isSelectionColumn">
			<div class="selection-summary">
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
		</template>

		<!-- Text columns: COUNT -->
		<template v-else-if="isTextColumn">
			<div class="count-summary">
				<span class="count-value">{{ rowCount }} {{ rowCount === 1 ? t('tablespro', 'item') : t('tablespro', 'items') }}</span>
			</div>
		</template>

		<!-- Other columns: empty -->
		<template v-else>
			<div class="empty-summary" />
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { DEFAULT_OPTION_COLOR } from '../../../constants.ts'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

export default {
	name: 'SummaryCell',

	components: {
		ChevronDownIcon,
		CheckIcon,
	},

	directives: {
		'click-outside': {
			bind(el, binding) {
				el._clickOutside = (event) => {
					if (!(el === event.target || el.contains(event.target))) {
						binding.value(event)
					}
				}
				// Delay to next tick to avoid catching the click that opened the dropdown
				setTimeout(() => {
					document.addEventListener('click', el._clickOutside)
				}, 0)
			},
			unbind(el) {
				document.removeEventListener('click', el._clickOutside)
			},
		},
	},

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

	data() {
		return {
			showDropdown: false,
			selectedFunction: this.getDefaultFunction(),
			dropdownPosition: { top: 0, left: 0 },
		}
	},

	created() {
		// Set default function based on column type
		this.selectedFunction = this.getDefaultFunction()
	},

	computed: {
		dropdownStyle() {
			return {
				position: 'fixed',
				top: `${this.dropdownPosition.top}px`,
				left: `${this.dropdownPosition.left}px`,
			}
		},

		aggregationFunctions() {
			return [
				{ id: 'sum', symbol: 'Σ', name: t('tablespro', 'Sum') },
				{ id: 'avg', symbol: 'x̄', name: t('tablespro', 'Average') },
				{ id: 'min', symbol: '↓', name: t('tablespro', 'Minimum') },
				{ id: 'max', symbol: '↑', name: t('tablespro', 'Maximum') },
				{ id: 'count', symbol: '#', name: t('tablespro', 'Count') },
				{ id: 'median', symbol: 'M̃', name: t('tablespro', 'Median') },
			]
		},

		currentFunctionLabel() {
			const func = this.aggregationFunctions.find(f => f.id === this.selectedFunction)
			return func ? func.symbol : 'Σ'
		},

		isNumberColumn() {
			return ['number', 'number-stars', 'number-progress'].includes(this.column.type)
		},

		isProgressColumn() {
			return this.column.type === 'number-progress'
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

		numericValues() {
			return this.rows
				.map(row => {
					const cell = row.data?.find(c => c.columnId === this.column.id)
					return parseFloat(cell?.value)
				})
				.filter(v => !isNaN(v))
		},

		calculatedValue() {
			const values = this.numericValues
			if (values.length === 0) return 0

			switch (this.selectedFunction) {
			case 'sum':
				return values.reduce((a, b) => a + b, 0)
			case 'avg':
				return values.reduce((a, b) => a + b, 0) / values.length
			case 'min':
				return Math.min(...values)
			case 'max':
				return Math.max(...values)
			case 'count':
				return values.length
			case 'median': {
				const sorted = [...values].sort((a, b) => a - b)
				const mid = Math.floor(sorted.length / 2)
				return sorted.length % 2 !== 0
					? sorted[mid]
					: (sorted[mid - 1] + sorted[mid]) / 2
			}
			default:
				return values.reduce((a, b) => a + b, 0)
			}
		},

		formattedValue() {
			// Count doesn't need formatting
			if (this.selectedFunction === 'count') {
				return this.calculatedValue.toString()
			}

			// Progress columns always show percentage
			if (this.isProgressColumn) {
				const formatted = new Intl.NumberFormat('sl-SI', {
					minimumFractionDigits: 0,
					maximumFractionDigits: 0,
				}).format(this.calculatedValue)
				return `${formatted}%`
			}

			const prefix = this.column.numberPrefix || ''
			const suffix = this.column.numberSuffix || ''
			const decimals = this.column.numberDecimals ?? 2

			const formatted = new Intl.NumberFormat('sl-SI', {
				minimumFractionDigits: 0,
				maximumFractionDigits: decimals,
			}).format(this.calculatedValue)

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

		getDefaultFunction() {
			// Progress columns default to average
			if (this.column.type === 'number-progress') {
				return 'avg'
			}
			// Stars default to average
			if (this.column.type === 'number-stars') {
				return 'avg'
			}
			// Other number columns default to sum
			return 'sum'
		},

		toggleDropdown() {
			if (this.showDropdown) {
				this.showDropdown = false
				return
			}

			// Calculate position based on trigger element
			const trigger = this.$refs.triggerRef
			if (trigger) {
				const rect = trigger.getBoundingClientRect()
				const dropdownHeight = 260 // Approximate height of dropdown
				const viewportHeight = window.innerHeight

				// Always open below if there's space, otherwise open above
				if (rect.bottom + dropdownHeight < viewportHeight) {
					// Show below the trigger (preferred for summary rows)
					this.dropdownPosition = {
						top: rect.bottom + 4,
						left: rect.left,
					}
				} else {
					// Show above the trigger if not enough space below
					this.dropdownPosition = {
						top: rect.top - dropdownHeight,
						left: rect.left,
					}
				}
			}

			this.showDropdown = true
		},

		closeDropdown() {
			this.showDropdown = false
		},

		selectFunction(funcId) {
			this.selectedFunction = funcId
			this.showDropdown = false
			// Emit event for parent to save preference if needed
			this.$emit('function-changed', { columnId: this.column.id, function: funcId })
		},
	},
}
</script>

<style lang="scss" scoped>
.summary-cell {
	padding: 2px 0;
	min-height: 24px;
	position: relative;
	display: flex;
	justify-content: flex-end;

	&.has-dropdown {
		cursor: pointer;
	}
}

.number-summary {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	cursor: pointer;
	margin-left: auto;

	.func-label {
		color: var(--color-text-maxcontrast);
		font-size: 13px;
		font-weight: 600;
		min-width: 16px;
		text-align: center;
	}

	.sum-value {
		color: var(--color-main-text);
		font-size: 17px;
		font-weight: 600;
	}

	.dropdown-icon {
		color: var(--color-text-maxcontrast);
		opacity: 0;
		transition: opacity 0.15s ease;
	}

	&:hover .dropdown-icon {
		opacity: 1;
	}

	&:hover .func-label {
		color: var(--color-primary-element);
	}
}

.aggregation-dropdown {
	// Position is set via inline style (fixed positioning)
	min-width: 160px;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: 8px;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
	z-index: 10000; // High z-index to appear above everything
	padding: 4px;

	.dropdown-item {
		display: flex;
		align-items: center;
		gap: 10px;
		width: 100%;
		padding: 8px 12px;
		border: none;
		background: none;
		cursor: pointer;
		border-radius: 6px;
		text-align: left;
		transition: background-color 0.15s ease;

		&:hover {
			background-color: var(--color-background-hover);
		}

		&.active {
			background-color: var(--color-primary-element-light);
		}

		.func-symbol {
			font-weight: 700;
			font-size: 14px;
			color: var(--color-primary-element);
			min-width: 20px;
			text-align: center;
		}

		.func-name {
			flex: 1;
			color: var(--color-main-text);
			font-size: 13px;
		}

		.check-icon {
			color: var(--color-primary-element);
		}
	}
}

.selection-summary {
	display: flex;
	position: absolute;
	inset: 0;
	margin: -4px -6px;

	.progress-segment {
		height: 100%;
		transition: width 0.3s ease;
		cursor: help;
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
