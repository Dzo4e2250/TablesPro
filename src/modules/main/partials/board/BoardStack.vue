<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="board-stack" :data-stack-id="stack.id">
		<div class="board-stack__header">
			<div class="board-stack__title-wrapper">
				<span v-if="stack.color"
					class="board-stack__color"
					:style="{ backgroundColor: stack.color }" />
				<h3 class="board-stack__title">{{ stack.title }}</h3>
			</div>
			<NcCounterBubble class="board-stack__count">
				{{ cards.length }}
			</NcCounterBubble>
		</div>

		<div class="board-stack__cards">
			<Container group-name="cards"
				:get-child-payload="getCardPayload"
				:should-accept-drop="() => canEdit"
				drag-class="board-card--ghost"
				drop-class="board-card--ghost-drop"
				@drop="onCardDrop">
				<Draggable v-for="card in cards" :key="card.id">
					<BoardCard
						:row="card"
						:columns="columns"
						:title-column-id="titleColumnId"
						:grouping-column-id="groupingColumnId"
						:can-edit="canEdit"
						@click="$emit('card-click', $event)"
						@edit="$emit('card-edit', $event)" />
				</Draggable>
			</Container>

			<div v-if="cards.length === 0" class="board-stack__empty">
				{{ t('tablespro', 'No cards') }}
			</div>
		</div>

		<div v-if="canEdit" class="board-stack__footer">
			<NcButton type="tertiary"
				:aria-label="t('tablespro', 'Add card')"
				@click="$emit('add-card', stack.id)">
				<template #icon>
					<PlusIcon :size="20" />
				</template>
				{{ t('tablespro', 'Add card') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { Container, Draggable } from 'vue-smooth-dnd'
import { NcButton, NcCounterBubble } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import BoardCard from './BoardCard.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'BoardStack',

	components: {
		Container,
		Draggable,
		NcButton,
		NcCounterBubble,
		PlusIcon,
		BoardCard,
	},

	props: {
		stack: {
			type: Object,
			required: true,
		},
		cards: {
			type: Array,
			default: () => [],
		},
		columns: {
			type: Array,
			default: () => [],
		},
		titleColumnId: {
			type: Number,
			default: null,
		},
		groupingColumnId: {
			type: Number,
			required: true,
		},
		canEdit: {
			type: Boolean,
			default: false,
		},
	},

	methods: {
		t,

		getCardPayload(index) {
			return this.cards[index]
		},

		onCardDrop(dropResult) {
			const { addedIndex, removedIndex, payload } = dropResult

			// Card was dropped into this stack
			if (addedIndex !== null) {
				this.$emit('card-drop', {
					card: payload,
					stackId: this.stack.id,
					stackValue: this.stack.value,
					addedIndex,
					removedIndex,
				})
			}
		},
	},
}
</script>

<style lang="scss" scoped>
$stack-width: 300px;
$stack-min-width: 280px;
$stack-gap: calc(var(--default-grid-baseline) * 2);

.board-stack {
	display: flex;
	flex-direction: column;
	width: $stack-width;
	min-width: $stack-min-width;
	max-height: 100%;
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-large);
	overflow: hidden;
}

.board-stack__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 3);
	background-color: var(--color-background-dark);
	position: sticky;
	top: 0;
	z-index: 10;
}

.board-stack__title-wrapper {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
	min-width: 0;
	flex: 1;
}

.board-stack__color {
	width: 12px;
	height: 12px;
	border-radius: 50%;
	flex-shrink: 0;
}

.board-stack__title {
	margin: 0;
	font-size: 14px;
	font-weight: 600;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.board-stack__count {
	flex-shrink: 0;
}

.board-stack__cards {
	flex: 1;
	overflow-y: auto;
	padding: 0 $stack-gap $stack-gap;

	// vue-smooth-dnd container styles
	:deep(.smooth-dnd-container) {
		display: flex;
		flex-direction: column;
		gap: $stack-gap;
		min-height: 50px;
	}

	:deep(.smooth-dnd-draggable-wrapper) {
		overflow: visible;
	}
}

.board-stack__empty {
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 80px;
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.board-stack__footer {
	padding: calc(var(--default-grid-baseline) * 2);
	border-top: 1px solid var(--color-border);

	:deep(.button-vue) {
		width: 100%;
		justify-content: flex-start;
	}
}

// Drag ghost styles
:deep(.board-card--ghost) {
	opacity: 0.5;
	background-color: var(--color-primary-element-light);
}

:deep(.board-card--ghost-drop) {
	border: 2px dashed var(--color-primary-element);
	background-color: var(--color-primary-element-light);
}
</style>
