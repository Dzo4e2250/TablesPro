<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="board-card" :class="{ 'board-card--dragging': dragging }" @click="$emit('click', row)">
		<div class="board-card__header">
			<h4 class="board-card__title">{{ cardTitle }}</h4>
			<NcButton v-if="canEdit"
				type="tertiary"
				class="board-card__menu"
				:aria-label="t('tablespro', 'Card options')"
				@click.stop="$emit('edit', row)">
				<template #icon>
					<DotsHorizontal :size="20" />
				</template>
			</NcButton>
		</div>

		<div v-if="visibleLabels.length > 0" class="board-card__labels">
			<span v-for="label in visibleLabels"
				:key="label.columnId"
				class="board-card__label"
				:style="getLabelStyle(label)">
				{{ label.value }}
			</span>
		</div>

		<div v-if="hasBadges" class="board-card__badges">
			<span v-if="hasDate" class="board-card__badge board-card__badge--date">
				<CalendarIcon :size="16" />
				{{ formattedDate }}
			</span>
			<span v-if="hasNumber" class="board-card__badge board-card__badge--number">
				{{ numberValue }}
			</span>
		</div>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'BoardCard',

	components: {
		NcButton,
		DotsHorizontal,
		CalendarIcon,
	},

	props: {
		row: {
			type: Object,
			required: true,
		},
		columns: {
			type: Array,
			default: () => [],
		},
		titleColumnId: {
			type: Number,
			default: null,
		},
		groupingColumnId: {
			type: Number,
			default: null,
		},
		canEdit: {
			type: Boolean,
			default: false,
		},
		dragging: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		cardTitle() {
			// Use title column if specified
			if (this.titleColumnId) {
				const cell = this.row.data?.find(d => d.columnId === this.titleColumnId)
				if (cell?.value) {
					return String(cell.value).substring(0, 100)
				}
			}

			// Otherwise find first text column
			const textColumn = this.columns.find(c =>
				c.type === 'text-line' || c.type === 'text-rich',
			)
			if (textColumn) {
				const cell = this.row.data?.find(d => d.columnId === textColumn.id)
				if (cell?.value) {
					// Strip HTML if rich text
					const value = String(cell.value).replace(/<[^>]*>/g, '')
					return value.substring(0, 100)
				}
			}

			// Fallback to row ID
			return `#${this.row.id}`
		},

		visibleLabels() {
			const labels = []
			const excludeTypes = ['text-line', 'text-rich', 'text-link']

			for (const column of this.columns) {
				// Skip grouping column and text columns
				if (column.id === this.groupingColumnId) continue
				if (column.id === this.titleColumnId) continue
				if (excludeTypes.includes(column.type)) continue
				if (column.id < 0) continue // Skip meta columns

				// Only show selection columns as labels
				if (column.type === 'selection' || column.type === 'selection-multi') {
					const cell = this.row.data?.find(d => d.columnId === column.id)
					if (cell?.value) {
						const values = Array.isArray(cell.value) ? cell.value : [cell.value]
						for (const val of values) {
							if (val) {
								const option = this.getSelectionOption(column, val)
								labels.push({
									columnId: column.id,
									value: option?.label || val,
									color: option?.color || null,
								})
							}
						}
					}
				}
			}

			return labels.slice(0, 4) // Limit to 4 labels
		},

		hasDate() {
			return this.columns.some(c => {
				if (c.type !== 'datetime' && c.type !== 'datetime-date') return false
				const cell = this.row.data?.find(d => d.columnId === c.id)
				return !!cell?.value
			})
		},

		formattedDate() {
			const dateColumn = this.columns.find(c =>
				(c.type === 'datetime' || c.type === 'datetime-date'),
			)
			if (!dateColumn) return ''

			const cell = this.row.data?.find(d => d.columnId === dateColumn.id)
			if (!cell?.value) return ''

			try {
				const date = new Date(cell.value)
				return date.toLocaleDateString()
			} catch {
				return cell.value
			}
		},

		hasNumber() {
			return this.columns.some(c => {
				if (c.type !== 'number') return false
				const cell = this.row.data?.find(d => d.columnId === c.id)
				return cell?.value !== null && cell?.value !== undefined
			})
		},

		numberValue() {
			const numberColumn = this.columns.find(c => c.type === 'number')
			if (!numberColumn) return ''

			const cell = this.row.data?.find(d => d.columnId === numberColumn.id)
			if (cell?.value === null || cell?.value === undefined) return ''

			return cell.value
		},

		hasBadges() {
			return this.hasDate || this.hasNumber
		},
	},

	methods: {
		t,

		getSelectionOption(column, value) {
			const options = column.selectionOptions || []
			return options.find(opt => opt.id === value || opt.label === value)
		},

		getLabelStyle(label) {
			if (label.color) {
				return {
					backgroundColor: label.color,
					color: this.getContrastColor(label.color),
				}
			}
			return {}
		},

		getContrastColor(hexColor) {
			// Remove # if present
			const hex = hexColor.replace('#', '')
			const r = parseInt(hex.substr(0, 2), 16)
			const g = parseInt(hex.substr(2, 2), 16)
			const b = parseInt(hex.substr(4, 2), 16)
			const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
			return luminance > 0.5 ? '#000000' : '#ffffff'
		},
	},
}
</script>

<style lang="scss" scoped>
.board-card {
	background-color: var(--color-main-background);
	border: 2px solid var(--color-border-dark);
	border-radius: var(--border-radius-large);
	padding: calc(var(--default-grid-baseline) * 2);
	cursor: pointer;
	transition: border-color 0.15s ease, box-shadow 0.15s ease;

	&:hover {
		border-color: var(--color-border-maxcontrast);
	}

	&:focus-visible {
		outline: 2px solid var(--color-primary-element);
		outline-offset: 2px;
	}

	&--dragging {
		opacity: 0.8;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	}
}

.board-card__header {
	display: flex;
	align-items: flex-start;
	gap: var(--default-grid-baseline);
}

.board-card__title {
	flex: 1;
	margin: 0;
	font-size: 14px;
	font-weight: normal;
	line-height: 1.4;
	word-wrap: break-word;
	overflow-wrap: break-word;
}

.board-card__menu {
	flex-shrink: 0;
	margin: -8px -8px 0 0;
	opacity: 0;
	transition: opacity 0.15s ease;

	.board-card:hover &,
	.board-card:focus-within & {
		opacity: 1;
	}
}

.board-card__labels {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 1);
	margin-top: calc(var(--default-grid-baseline) * 2);
}

.board-card__label {
	display: inline-block;
	padding: 2px 8px;
	font-size: 11px;
	font-weight: 500;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-primary-element-light);
	color: var(--color-primary-element);
	max-width: 120px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.board-card__badges {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-top: calc(var(--default-grid-baseline) * 2);
	color: var(--color-text-maxcontrast);
	font-size: 12px;
}

.board-card__badge {
	display: inline-flex;
	align-items: center;
	gap: 4px;

	&--date {
		color: var(--color-text-maxcontrast);
	}

	&--number {
		font-weight: 500;
	}
}
</style>
