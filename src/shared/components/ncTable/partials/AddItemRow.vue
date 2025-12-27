<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr class="add-item-row">
		<td :colspan="columnsCount + 2" class="add-item-cell">
			<NcButton type="tertiary" @click="$emit('add-item')">
				<template #icon>
					<PlusIcon :size="16" />
				</template>
				{{ t('tablespro', 'Add item') }}
			</NcButton>
		</td>
	</tr>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'AddItemRow',

	components: {
		NcButton,
		PlusIcon,
	},

	props: {
		columnsCount: {
			type: Number,
			default: 0,
		},
	},

	methods: {
		t,
	},
}
</script>

<style lang="scss" scoped>
.add-item-row {
	background-color: var(--color-background-dark) !important;

	&:hover {
		background-color: var(--color-background-hover) !important;
	}
}

.add-item-cell {
	padding: 4px 16px !important;
	border: none !important;
}
</style>
