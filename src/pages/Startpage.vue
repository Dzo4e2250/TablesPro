<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcEmptyContent :name="t('tablespro', 'Tables')"
			:description="t('tablespro', 'Manage data the way you need it.')">
			<template #icon>
				<IconTables />
			</template>
			<template #action>
				<NcButton :aria-label="t('tablespro', 'Create new table')" type="primary" @click="addTable">
					{{ t('tablespro', 'Create new table') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<MainModals />
	</div>
</template>

<script>
import { NcEmptyContent, NcButton } from '@nextcloud/vue'
import IconTables from '../shared/assets/icons/IconTables.vue'
import { emit } from '@nextcloud/event-bus'
import MainModals from '../modules/modals/Modals.vue'

export default {
	components: {
		NcEmptyContent,
		NcButton,
		IconTables,
		MainModals,
	},
	methods: {
		addTable() {
			emit('tables:table:create', {})
		},
	},
}
</script>

<style scoped>

.empty-content {
	margin-top: 20vh;
}

</style>
