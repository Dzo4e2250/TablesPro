<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="cell-selection">
		<div v-if="!isEditing" class="non-edit-mode" @click="handleStartEditing">
			<span
				v-if="value !== null"
				class="status-badge"
				:style="{
					backgroundColor: getOptionColor,
					color: getContrastTextColor
				}">
				{{ column.getLabel(value) }}
			</span>
			<span v-else class="empty-value">-</span>
			<span v-if="isDeleted()" :title="t('tablespro', 'This option is outdated.')">&nbsp;⚠️</span>
		</div>
		<div v-else
			ref="editingContainer"
			class="edit-mode"
			tabindex="0"
			@keydown.enter.stop="saveChanges"
			@keydown.escape.stop="cancelEdit">
			<NcSelect v-model="editValue"
				:options="getAllNonDeletedOptions"
				:aria-label-combobox="t('tablespro', 'Options')"
				:disabled="localLoading || !canEditCell()"
				style="width: 100%;">
				<template #option="{ label, color }">
					<span
						class="select-option-badge"
						:style="{
							backgroundColor: color || DEFAULT_OPTION_COLOR,
							color: getContrastColor(color || DEFAULT_OPTION_COLOR)
						}">
						{{ label }}
					</span>
				</template>
				<template #selected-option="{ label, color }">
					<span
						class="select-option-badge"
						:style="{
							backgroundColor: color || DEFAULT_OPTION_COLOR,
							color: getContrastColor(color || DEFAULT_OPTION_COLOR)
						}">
						{{ label }}
					</span>
				</template>
			</NcSelect>
			<div v-if="localLoading" class="loading-indicator">
				<div class="icon-loading-small icon-loading-inline" />
			</div>
		</div>
	</div>
</template>

<script>
import { NcSelect } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import cellEditMixin from '../mixins/cellEditMixin.js'
import { DEFAULT_OPTION_COLOR, getContrastColor } from '../../../constants.ts'

export default {
	name: 'TableCellSelection',

	components: {
		NcSelect,
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
			isInitialEditClick: false,
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
	},

	watch: {
		isEditing(isEditing) {
			if (isEditing) {
				this.initEditValue()
				// Add click outside listener after the current event loop
				// to avoid the same click that triggered editing from closing the editor
				this.$nextTick(() => {
					document.addEventListener('click', this.handleClickOutside)
				})
			} else {
				// Remove click outside listener
				document.removeEventListener('click', this.handleClickOutside)
				this.isInitialEditClick = false
			}
		},
	},

	methods: {
		t,
		getContrastColor,

		handleStartEditing(event) {
			this.isInitialEditClick = true
			this.startEditing()
			// Stop the event from propagating to avoid immediate click outside
			event.stopPropagation()
		},

		isDeleted() {
			return this.column.isDeletedLabel(this.value)
		},

		getOptionObject(id) {
			return this.getOptions.find(e => e.id === id) || null
		},

		initEditValue() {
			if (this.value !== null) {
				this.editValue = this.getOptionObject(parseInt(this.value))
			} else {
				this.editValue = null
			}
		},
		async saveChanges() {
			if (this.localLoading) {
				return
			}

			const newValue = this.editValue?.id

			const success = await this.updateCellValue(newValue)

			if (!success) {
				this.cancelEdit()
			}

			this.localLoading = false
			this.isEditing = false
		},

		handleClickOutside(event) {
			// Ignore the initial click that started editing
			if (this.isInitialEditClick) {
				this.isInitialEditClick = false
				return
			}

			// Check if the click is outside the editing container
			if (this.$refs.editingContainer && !this.$refs.editingContainer.contains(event.target)) {
				this.saveChanges()
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.cell-selection {
	width: 100%;

	.non-edit-mode {
		cursor: pointer;
		min-height: 20px;
	}
}

:deep(.vs__dropdown-toggle) {
    border: var(--vs-border-width) var(--vs-border-style) var(--vs-border-color);
    border-radius: var(--vs-border-radius);
}

.edit-mode {
	.icon-loading-inline {
		margin-inline-start: 4px;
	}
}

span {
	cursor: help;
}

.status-badge,
.select-option-badge {
	display: inline-block;
	padding: 4px 12px;
	border-radius: 12px;
	font-size: 13px;
	font-weight: 500;
	white-space: nowrap;
}

.empty-value {
	color: var(--color-text-maxcontrast);
}
</style>
