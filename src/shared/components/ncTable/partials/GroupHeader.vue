<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr class="group-header" :class="{ collapsed: !expanded }" @click="$emit('toggle')">
		<td :colspan="columnsCount + 2" class="group-header-cell">
			<div class="group-header-content">
				<button class="expand-btn" :aria-label="expanded ? t('tablespro', 'Collapse') : t('tablespro', 'Expand')">
					<ChevronRightIcon v-if="!expanded" :size="20" />
					<ChevronDownIcon v-else :size="20" />
				</button>

				<span
					class="group-label"
					:style="groupColor ? { backgroundColor: groupColor, color: getContrastColor(groupColor) } : {}">
					{{ label }}
				</span>

				<span class="group-count">
					({{ rows.length }} {{ rows.length === 1 ? t('tablespro', 'item') : t('tablespro', 'items') }})
				</span>

				<span v-if="primarySum !== null" class="group-sum">
					{{ formattedPrimarySum }}
				</span>
			</div>
		</td>
	</tr>
</template>

<script>
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'
import { translate as t } from '@nextcloud/l10n'
import { getContrastColor } from '../../../constants.ts'

export default {
	name: 'GroupHeader',

	components: {
		ChevronRightIcon,
		ChevronDownIcon,
	},

	props: {
		label: {
			type: String,
			required: true,
		},
		rows: {
			type: Array,
			default: () => [],
		},
		columns: {
			type: Array,
			default: () => [],
		},
		expanded: {
			type: Boolean,
			default: true,
		},
		groupColor: {
			type: String,
			default: null,
		},
	},

	computed: {
		columnsCount() {
			return this.columns.length
		},

		numberColumn() {
			return this.columns.find(col => col.type === 'number')
		},

		primarySum() {
			if (!this.numberColumn) return null

			return this.rows.reduce((sum, row) => {
				const cell = row.data?.find(c => c.columnId === this.numberColumn.id)
				return sum + (parseFloat(cell?.value) || 0)
			}, 0)
		},

		formattedPrimarySum() {
			if (this.primarySum === null) return ''

			const prefix = this.numberColumn?.numberPrefix || ''
			const suffix = this.numberColumn?.numberSuffix || ''
			const decimals = this.numberColumn?.numberDecimals ?? 2

			const formatted = new Intl.NumberFormat('sl-SI', {
				minimumFractionDigits: 0,
				maximumFractionDigits: decimals,
			}).format(this.primarySum)

			return `${prefix}${formatted}${suffix}`
		},
	},

	methods: {
		t,
		getContrastColor,
	},
}
</script>

<style lang="scss" scoped>
.group-header {
	cursor: pointer;
	user-select: none;
	background-color: var(--color-background-dark) !important;

	&:hover {
		background-color: var(--color-background-hover) !important;
	}

	&.collapsed {
		border-bottom: 2px solid var(--color-border);
	}
}

.group-header-cell {
	padding: 12px 16px !important;
	border: none !important;
}

.group-header-content {
	display: flex;
	align-items: center;
	gap: 12px;
}

.expand-btn {
	background: none;
	border: none;
	padding: 4px;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 4px;
	color: var(--color-main-text);

	&:hover {
		background-color: var(--color-background-hover);
	}
}

.group-label {
	font-weight: 600;
	font-size: 14px;
	padding: 4px 12px;
	border-radius: 12px;
}

.group-count {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.group-sum {
	margin-inline-start: auto;
	font-weight: 600;
	color: var(--color-main-text);
	font-size: 14px;
}
</style>
