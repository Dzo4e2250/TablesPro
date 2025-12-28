<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="cell-number" :style="{ opacity: !canEditCell() ? 0.6 : 1 }">
		<div v-if="!isEditing" class="number-display-wrapper">
			<button
				v-if="canEditCell() && !localLoading"
				class="stepper-btn stepper-minus"
				:disabled="isAtMin"
				:title="t('tablespro', 'Decrease')"
				@click.stop="decrement">
				<Minus :size="14" />
			</button>
			<div class="number-display" @click="startEditing">
				<template v-if="getValue !== null">
					{{ column.numberPrefix }}{{ getValue }}{{ column.numberSuffix }}
				</template>
				<span v-else class="empty-placeholder">—</span>
			</div>
			<button
				v-if="canEditCell() && !localLoading"
				class="stepper-btn stepper-plus"
				:disabled="isAtMax"
				:title="t('tablespro', 'Increase')"
				@click.stop="increment">
				<Plus :size="14" />
			</button>
			<div v-if="localLoading" class="icon-loading-small" />
		</div>
		<div v-else class="inline-editing-container">
			<div v-if="column.numberPrefix" class="number-prefix">
				{{ column.numberPrefix }}
			</div>
			<input ref="input" v-model="editValue" type="number" :min="getMin" :max="getMax" :step="getStep"
				:disabled="localLoading || !canEditCell()" class="cell-input" @blur="saveChanges"
				@keyup.enter="saveChanges" @keyup.esc="cancelEdit">
			<div v-if="column.numberSuffix" class="number-suffix">
				{{ column.numberSuffix }}
			</div>
			<div v-if="localLoading" class="icon-loading-small icon-loading-inline" />
		</div>
	</div>
</template>

<script>
import cellEditMixin from '../mixins/cellEditMixin.js'
import Plus from 'vue-material-design-icons/Plus.vue'
import Minus from 'vue-material-design-icons/Minus.vue'

export default {
	name: 'TableCellNumber',

	components: {
		Plus,
		Minus,
	},

	mixins: [cellEditMixin],

	props: {
		value: {
			type: Number,
			default: null,
		},
	},

	computed: {
		getValue() {
			if (this.value === null) {
				return null
			}
			return this.value.toFixed(this.column?.numberDecimals)
		},

		getStep() {
			return Math.pow(10, -(this.column?.numberDecimals || 0))
		},
		getMin() {
			if (this.column?.numberMin !== undefined) {
				return this.column.numberMin
			} else {
				return null
			}
		},
		getMax() {
			if (this.column?.numberMax !== undefined) {
				return this.column.numberMax
			} else {
				return null
			}
		},
		isAtMin() {
			if (this.getMin === null) return false
			return this.value !== null && this.value <= this.getMin
		},
		isAtMax() {
			if (this.getMax === null) return false
			return this.value !== null && this.value >= this.getMax
		},
	},

	methods: {
		async increment() {
			if (this.localLoading) return
			const currentValue = this.value ?? 0
			let newValue = currentValue + 1
			if (this.getMax !== null && newValue > this.getMax) {
				newValue = this.getMax
			}
			await this.updateCellValue(newValue)
			this.localLoading = false
		},

		async decrement() {
			if (this.localLoading) return
			const currentValue = this.value ?? 0
			let newValue = currentValue - 1
			if (this.getMin !== null && newValue < this.getMin) {
				newValue = this.getMin
			}
			await this.updateCellValue(newValue)
			this.localLoading = false
		},

		async saveChanges() {
			// Prevent multiple executions of saveChanges
			if (this.localLoading) {
				return
			}

			if (Number(this.editValue) === this.value) {
				this.isEditing = false
				return
			}

			let newValue = this.editValue === '' ? null : Number(this.editValue)

			if (newValue !== null && !isNaN(newValue)) {
				if (this.getMin !== null && newValue < this.getMin) {
					newValue = this.getMin
				}
				if (this.getMax !== null && newValue > this.getMax) {
					newValue = this.getMax
				}
			}

			const success = await this.updateCellValue(newValue)

			if (!success) {
				this.cancelEdit()
			}

			this.localLoading = false
			this.isEditing = false
		},
	},
}
</script>

<style scoped>
.cell-number {
	width: 100%;
	text-align: end;
}

.number-display-wrapper {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 2px;
}

.number-display {
	cursor: pointer;
	flex-grow: 1;
	text-align: end;
}

.stepper-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 18px;
	height: 18px;
	padding: 0;
	margin: 0;
	border: none;
	border-radius: var(--border-radius-small);
	background: transparent;
	color: var(--color-text-maxcontrast);
	cursor: pointer;
	opacity: 0;
	transition: opacity 0.15s ease, background-color 0.15s ease, color 0.15s ease;

	&:hover:not(:disabled) {
		background-color: var(--color-primary-element-light);
		color: var(--color-primary-element);
	}

	&:disabled {
		cursor: not-allowed;
		opacity: 0.3 !important;
	}
}

.number-display-wrapper:hover .stepper-btn {
	opacity: 1;
}

.inline-editing-container {
	display: flex;
	align-items: center;
}

.cell-input {
	text-align: end;
	flex-grow: 1;
}

.number-prefix,
.number-suffix {
	padding: 0 4px;
}

.empty-placeholder {
	color: var(--color-text-maxcontrast);
	opacity: 0.5;
}
</style>
