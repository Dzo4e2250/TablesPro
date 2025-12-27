<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSelect v-model="combinedTypeObject"
		class="columnTypeSelection"
		:options="typeOptions"
		:clearable="false"
		:aria-label-combobox="t('tablespro', 'Column type')"
		style="width: 100%">
		<template #option="props">
			<div class="icon-label-container">
				<TextLongIcon v-if="props.id === 'text'" />
				<LinkIcon v-if="props.id === 'text-link'" />
				<CounterIcon v-if="props.id === 'number'" />
				<StarIcon v-if="props.id === 'number-stars'" />
				<ProgressIcon v-if="props.id === 'number-progress'" />
				<SelectionIcon v-if="props.id === 'selection'" />
				<DatetimeIcon v-if="props.id === 'datetime'" />
				<ContactsIcon v-if="props.id === 'usergroup'" />
				<div class="multiSelectOptionLabel">
					{{ props.label }}
				</div>
			</div>
		</template>
		<template #selected-option="props">
			<TextLongIcon v-if="props.id === 'text'" />
			<LinkIcon v-if="props.id === 'text-link'" />
			<CounterIcon v-if="props.id === 'number'" />
			<StarIcon v-if="props.id === 'number-stars'" />
			<ProgressIcon v-if="props.id === 'number-progress'" />
			<SelectionIcon v-if="props.id === 'selection'" />
			<DatetimeIcon v-if="props.id === 'datetime'" />
			<ContactsIcon v-if="props.id === 'usergroup'" />
			<div class="multiSelectOptionLabel">
				{{ props.label }}
			</div>
		</template>
	</NcSelect>
</template>

<script>
import '@nextcloud/dialogs/style.css'
import TextLongIcon from 'vue-material-design-icons/TextLong.vue'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import CounterIcon from 'vue-material-design-icons/Counter.vue'
import StarIcon from 'vue-material-design-icons/StarOutline.vue'
import ProgressIcon from 'vue-material-design-icons/ArrowRightThin.vue'
import SelectionIcon from 'vue-material-design-icons/FormSelect.vue'
import DatetimeIcon from 'vue-material-design-icons/ClipboardTextClockOutline.vue'
import ContactsIcon from 'vue-material-design-icons/ContactsOutline.vue'
import { NcSelect } from '@nextcloud/vue'

export default {
	components: {
		SelectionIcon,
		DatetimeIcon,
		StarIcon,
		ProgressIcon,
		CounterIcon,
		LinkIcon,
		TextLongIcon,
		NcSelect,
		ContactsIcon,
	},
	props: {
		columnId: {
			type: String,
			default: null,
		},
	},
	data() {
		return {
			type: 'text',
			subtype: 'line',
			typeOptions: [
				{ id: 'text', label: t('tablespro', 'Text') },
				{ id: 'text-link', label: t('tablespro', 'Link') },

				{ id: 'number', label: t('tablespro', 'Number') },
				{ id: 'number-stars', label: t('tablespro', 'Stars rating') },
				{ id: 'number-progress', label: t('tablespro', 'Progress bar') },

				{ id: 'selection', label: t('tablespro', 'Selection') },

				{ id: 'datetime', label: t('tablespro', 'Date and time') },
				{ id: 'usergroup', label: t('tablespro', 'Users and groups') },
			],
		}
	},
	computed: {
		combinedType: {
			get() {
				return this.type ? this.type + ((this.subtype) ? ('-' + this.subtype) : '') : null
			},
			set(newValue) {
				if (newValue) {
					const types = newValue.split('-')
					this.type = types[0]
					this.subtype = types[1] || ''
					if (this.type === 'text' && !this.subtype) {
						// default subtype for type text
						this.subtype = 'line'
					}
					this.$emit('update:columnId', this.combinedType)
				}
			},
		},
		combinedTypeObject: {
			get() {
				const type = this.type
				let subtype = this.subtype

				if (type === 'text' && subtype !== 'link') {
					subtype = null
				}

				if (type === 'selection') {
					subtype = null
				}

				if (type === 'datetime') {
					subtype = null
				}

				let id = type
				if (subtype !== null && subtype !== '') {
					id += '-' + subtype
				}

				return this.combinedType ? this.typeOptions.filter(item => item.id === id) : null
			},
			set(o) {
				if (o) this.combinedType = o.id
			},
		},
	},
	watch: {
		columnId() {
			this.combinedType = this.columnId
		},
	},
	mounted() {
		this.combinedType = this.columnId
	},
}
</script>
<style lang="scss" scoped>

.typeSelections {
	display: inline-flex;
}

.typeSelections span {
	padding-inline-end: 21px;
}

.multiSelectOptionLabel {
	padding-inline-start: calc(var(--default-grid-baseline) * 1);
}

.icon-label-container {
	display: flex;
	align-items: center;
}

</style>
