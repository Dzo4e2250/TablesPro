<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="group-by-selector">
		<NcSelect
			v-model="selectedColumn"
			:options="groupableOptions"
			:placeholder="t('tablespro', 'Group by...')"
			:clearable="true"
			:aria-label-combobox="t('tablespro', 'Group by column')"
			@input="handleInput">
			<template #selected-option="{ label }">
				<div class="selected-group">
					<GroupIcon :size="16" />
					<span>{{ label }}</span>
				</div>
			</template>
		</NcSelect>
	</div>
</template>

<script>
import { NcSelect } from '@nextcloud/vue'
import GroupIcon from 'vue-material-design-icons/Group.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'GroupBySelector',

	components: {
		NcSelect,
		GroupIcon,
	},

	props: {
		columns: {
			type: Array,
			default: () => [],
		},
		groupBy: {
			type: Number,
			default: null,
		},
	},

	computed: {
		selectedColumn: {
			get() {
				return this.groupableOptions.find(opt => opt.id === this.groupBy) || null
			},
			set() {
				// Handled by @input
			},
		},

		groupableOptions() {
			return this.columns
				.filter(col => ['selection', 'text-line'].includes(col.type))
				.map(col => ({
					id: col.id,
					label: col.title,
				}))
		},
	},

	methods: {
		t,
		handleInput(value) {
			this.$emit('update:groupBy', value?.id || null)
		},
	},
}
</script>

<style lang="scss" scoped>
.group-by-selector {
	min-width: 150px;

	:deep(.v-select) {
		min-width: 150px;

		.vs__dropdown-toggle {
			border: 1px solid var(--color-border);
			border-radius: var(--border-radius);
			background: var(--color-main-background);
		}
	}
}

.selected-group {
	display: flex;
	align-items: center;
	gap: 6px;
}
</style>
