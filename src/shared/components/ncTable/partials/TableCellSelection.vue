<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="cell-selection"
		ref="cellRef"
		:class="{ 'has-value': value !== null }"
		:style="value !== null ? {
			backgroundColor: getOptionColor,
			color: getContrastTextColor
		} : {}"
		@click.stop="toggleDropdown">
		<div class="cell-content">
			<span v-if="value !== null" class="status-label">
				{{ column.getLabel(value) }}
			</span>
			<span v-else class="empty-value">-</span>
			<span v-if="isDeleted()" :title="t('tablespro', 'This option is outdated.')">&nbsp;⚠️</span>
			<ChevronDown v-if="canEditCell()" class="dropdown-arrow" :size="16" />
			<div v-if="localLoading" class="icon-loading-small" />
		</div>

		<!-- Dropdown menu -->
		<div v-if="showDropdown"
			v-click-outside="closeDropdown"
			class="options-dropdown"
			:style="dropdownStyle">
			<button
				v-for="option in getAllNonDeletedOptions"
				:key="option.id"
				class="option-item"
				:class="{ active: value === option.id }"
				:style="{
					backgroundColor: option.color || DEFAULT_OPTION_COLOR,
					color: getContrastColor(option.color || DEFAULT_OPTION_COLOR)
				}"
				@click.stop="selectOption(option)">
				{{ option.label }}
				<Check v-if="value === option.id" :size="16" />
			</button>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import cellEditMixin from '../mixins/cellEditMixin.js'
import { DEFAULT_OPTION_COLOR, getContrastColor } from '../../../constants.ts'
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import Check from 'vue-material-design-icons/Check.vue'

export default {
	name: 'TableCellSelection',

	components: {
		ChevronDown,
		Check,
	},

	directives: {
		'click-outside': {
			bind(el, binding) {
				el._clickOutside = (event) => {
					if (!(el === event.target || el.contains(event.target))) {
						binding.value(event)
					}
				}
				setTimeout(() => {
					document.addEventListener('click', el._clickOutside)
				}, 0)
			},
			unbind(el) {
				document.removeEventListener('click', el._clickOutside)
			},
		},
	},

	mixins: [cellEditMixin],

	props: {
		column: {
			type: Object,
			default: () => {},
		},

		rowId: {
			type: Number,
			default: null,
		},

		value: {
			type: Number,
			default: null,
		},
	},

	data() {
		return {
			showDropdown: false,
			dropdownPosition: { top: 0, left: 0 },
			DEFAULT_OPTION_COLOR,
		}
	},

	computed: {
		getOptions() {
			return this.column?.selectionOptions || []
		},
		getAllNonDeletedOptions() {
			return this.getOptions.filter(item => {
				return !item.deleted
			})
		},
		getOptionColor() {
			const option = this.getOptionObject(this.value)
			return option?.color || DEFAULT_OPTION_COLOR
		},
		getContrastTextColor() {
			return getContrastColor(this.getOptionColor)
		},
		dropdownStyle() {
			return {
				position: 'fixed',
				top: `${this.dropdownPosition.top}px`,
				left: `${this.dropdownPosition.left}px`,
			}
		},
	},

	methods: {
		t,
		getContrastColor,

		toggleDropdown() {
			if (!this.canEditCell()) return

			if (this.showDropdown) {
				this.showDropdown = false
				return
			}

			const cell = this.$refs.cellRef
			if (cell) {
				const rect = cell.getBoundingClientRect()
				this.dropdownPosition = {
					top: rect.bottom + 4,
					left: rect.left,
				}
			}
			this.showDropdown = true
		},

		closeDropdown() {
			this.showDropdown = false
		},

		isDeleted() {
			return this.column.isDeletedLabel(this.value)
		},

		getOptionObject(id) {
			return this.getOptions.find(e => e.id === id) || null
		},

		async selectOption(option) {
			if (this.localLoading) return

			this.showDropdown = false

			if (option.id === this.value) return

			await this.updateCellValue(option.id)
			this.localLoading = false
		},
	},
}
</script>

<style lang="scss" scoped>
.cell-selection {
	width: 100%;
	margin: -2px -6px;
	padding: 4px 8px;
	cursor: pointer;
	position: relative;

	.cell-content {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 4px;
		min-height: 20px;
	}

	.dropdown-arrow {
		position: absolute;
		right: 4px;
		opacity: 0;
		transition: opacity 0.15s ease;
	}

	&:hover .dropdown-arrow {
		opacity: 0.7;
	}
}

.status-label {
	font-size: 13px;
	font-weight: 500;
	white-space: nowrap;
}

.empty-value {
	color: var(--color-text-maxcontrast);
}

.options-dropdown {
	min-width: 150px;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: 8px;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
	z-index: 10000;
	padding: 4px;
	display: flex;
	flex-direction: column;
	gap: 2px;

	.option-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 8px;
		width: 100%;
		padding: 8px 12px;
		border: none;
		cursor: pointer;
		border-radius: 6px;
		text-align: left;
		font-size: 13px;
		font-weight: 500;
		transition: filter 0.15s ease;

		&:hover {
			filter: brightness(0.95);
		}

		&.active {
			filter: brightness(0.9);
		}
	}
}
</style>
