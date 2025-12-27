<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-if="activeElement" class="integration">
		<h3>{{ t('tablespro', 'API') }}</h3>
		<p>
			{{ t('tablespro', 'This is your API endpoint for this view') }}
		</p>
		<NcInputField id="urlTextField"
			:value="apiEndpointUrl"
			:show-trailing-button="true"
			:trailing-button-label="t('files', 'Copy to clipboard')"
			readonly="readonly"
			:success="copied"
			type="url"
			@focus="$event.target.select()"
			@trailing-button-click="copyUrl">
			<template #trailing-button-icon>
				<ContentCopy :size="20" />
			</template>
		</NcInputField>
		<h4>
			{{ t('tablespro', 'Your permissions') }}
		</h4>
		<ul>
			<li :class="{'notPermitted': !canReadData(activeElement)}">
				{{ canReadData(activeElement) ? '✓' : '' }} {{ t('tablespro', 'Read') }}
			</li>
			<li :class="{'notPermitted': !canCreateRowInElement(activeElement)}">
				{{ canCreateRowInElement(activeElement) ? '✓' : '' }} {{ t('tablespro', 'Create') }}
			</li>
			<li :class="{'notPermitted': !canUpdateData(activeElement)}">
				{{ canUpdateData(activeElement) ? '✓' : '' }} {{ t('tablespro', 'Update') }}
			</li>
			<li :class="{'notPermitted': !canDeleteData(activeElement)}">
				{{ canDeleteData(activeElement) ? '✓' : '' }} {{ t('tablespro', 'Delete') }}
			</li>
			<li :class="{'notPermitted': !canManageElement(activeElement)}">
				{{ canManageElement(activeElement) ? '✓' : '' }} {{ t('tablespro', 'Manage') }}
			</li>
		</ul>
	</div>
</template>

<script>
import { mapState } from 'pinia'
import { useTablesStore } from '../../../store/store.js'
import { generateUrl } from '@nextcloud/router'
import permissionsMixin from '../../../shared/components/ncTable/mixins/permissionsMixin.js'
import copyToClipboard from '../../../shared/mixins/copyToClipboard.js'

import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'

export default {
	components: {
		NcInputField,
		ContentCopy,
	},

	mixins: [permissionsMixin, copyToClipboard],

	data() {
		return {
			loading: false,
			copied: false,
		}
	},

	computed: {
		...mapState(useTablesStore, ['tablespro', 'activeElement', 'isLoadingSomething', 'isView']),
		apiEndpointUrl() {
			const params = {
				elementId: this.activeElement.id,
			}
			const url = '/apps/tables/api/1/' + (this.isView ? 'views' : 'tablespro') + '/{elementId}'
			return window.location.protocol + '//' + window.location.host + generateUrl(url, params)
		},
	},
	 methods: {
		copyUrl() {
			this.copyToClipboard(this.apiEndpointUrl, false)
		},
	 },
}
</script>
<style lang="scss" scoped>

.url {
	font-style: italic;
	overflow-x: auto
}

h4 {
	margin-top:calc(var(--default-grid-baseline) * 3);
}

ul {
	margin-inline-start: calc(var(--default-grid-baseline) * 3);
}

.notPermitted {
	padding-inline-start: 17px;
}

</style>
