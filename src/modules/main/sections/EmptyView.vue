<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent :name="t('tablespro', 'No columns selected')"
		:description="t('tablespro', 'The view is empty. Edit which columns should be displayed.')">
		<template #icon>
			{{ view.emoji }}
		</template>
		<template #action>
			<NcButton :aria-label="t('table', 'Edit view')" type="primary" @click="editView()">
				{{ t('tablespro', 'Edit view') }}
			</NcButton>
		</template>
	</NcEmptyContent>
</template>
<script>
import { NcEmptyContent, NcButton } from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'

export default {
	name: 'EmptyView',
	components: {
		NcEmptyContent,
		NcButton,
	},
	props: {
		view: {
			type: Object,
			default: null,
		},
	},
	methods: {
		editView() {
			emit('tables:view:edit', { view: this.view, viewSetting: {} })
		},
	},
}
</script>

<style scoped>

:deep(.empty-content__icon) {
	font-size: xxx-large;
	opacity: 1;
}

.empty-content {
	margin-top: 20vh;
}

</style>
