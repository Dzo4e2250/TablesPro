<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="board-stack" :class="{ 'board-stack--drag-over': isDragOver }" :data-stack-id="stack.id">
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
			<Container
				group-name="stack"
				orientation="vertical"
				:get-child-payload="getCardPayload"
				drag-class="board-card--ghost"
				drop-class="board-card--ghost-drop"
				:drop-placeholder="dropPlaceholderOptions"
				:animation-duration="150"
				@should-accept-drop="() => canEdit"
				@drag-enter="onDragEnter"
				@drag-leave="onDragLeave"
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
				<!-- Empty state inside container for better drop detection -->
				<div v-if="cards.length === 0" class="board-stack__empty-drop">
					{{ t('tablespro', 'Drop cards here') }}
				</div>
			</Container>
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

	data() {
		return {
			isDragOver: false,
			dropPlaceholderOptions: {
				className: 'board-card-drop-placeholder',
				animationDuration: 200,
				showOnTop: true,
			},
		}
	},

	methods: {
		t,

		getCardPayload(index) {
			return this.cards[index]
		},

		onDragEnter() {
			this.isDragOver = true
		},

		onDragLeave() {
			this.isDragOver = false
		},

		onCardDrop(dropResult) {
			this.isDragOver = false
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
$stack-min-width: 216px;
$stack-max-width: 300px;
$stack-gap: calc(var(--default-grid-baseline) * 3);

.board-stack {
	display: flex;
	flex-direction: column;
	flex: 0 0 $stack-max-width;
	min-width: $stack-min-width;
	max-width: $stack-max-width;
	height: 100%;
	background-color: var(--color-main-background);
	transition: box-shadow 0.2s ease;

	&--drag-over {
		box-shadow: 0 0 12px rgba(var(--color-primary-element-rgb), 0.3);
		background-color: var(--color-primary-element-light);
	}
}

.board-stack__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: calc(var(--default-grid-baseline) * 2);
	background-color: var(--color-main-background);
	position: sticky;
	top: 0;
	z-index: 100;
	cursor: grab;

	// Smooth fade out gradient like Deck
	&::after {
		content: '';
		display: block;
		position: absolute;
		width: 100%;
		height: $stack-gap;
		bottom: 0;
		left: 0;
		z-index: 99;
		background: linear-gradient(180deg, var(--color-main-background) 0%, transparent 100%);
		transform: translateY(100%);
		pointer-events: none;
	}
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
	flex: 1 1 auto;
	overflow-y: auto;
	overflow-x: hidden;
	padding: 0 $stack-gap;
	scrollbar-gutter: stable;
	min-height: 0;

	// vue-smooth-dnd container styles
	:deep(.smooth-dnd-container) {
		display: flex;
		flex-direction: column;
		gap: $stack-gap;
		min-height: 100%;
		padding: $stack-gap 0;
	}

	:deep(.smooth-dnd-container.vertical) {
		min-height: 100%;
	}

	:deep(.smooth-dnd-draggable-wrapper) {
		overflow: visible;
	}
}

.board-stack__empty-drop {
	display: flex;
	align-items: center;
	justify-content: center;
	flex: 1;
	min-height: 200px;
	color: var(--color-text-maxcontrast);
	font-size: 13px;
	border: 2px dashed var(--color-border);
	border-radius: var(--border-radius-large);
	margin: $stack-gap 0;
	background-color: var(--color-background-hover);
}

.board-stack__footer {
	flex-shrink: 0;
	padding: $stack-gap;
	padding-top: 0;
	background-color: var(--color-main-background);
	position: sticky;
	bottom: 0;
	z-index: 100;

	// Smooth fade gradient like Deck
	&::before {
		content: '';
		display: block;
		position: absolute;
		width: 100%;
		height: $stack-gap;
		top: 0;
		left: 0;
		z-index: 99;
		background: linear-gradient(0deg, var(--color-main-background) 0%, transparent 100%);
		transform: translateY(-100%);
		pointer-events: none;
	}

	:deep(.button-vue) {
		width: 100%;
		justify-content: flex-start;
	}
}

// Drag ghost styles
:deep(.board-card--ghost) {
	opacity: 0.8;
	transform: rotate(3deg);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

:deep(.board-card--ghost-drop) {
	border: 2px dashed var(--color-primary-element);
	background-color: var(--color-primary-element-light);
}

// Drop placeholder - shows where card will land
:deep(.board-card-drop-placeholder) {
	background-color: var(--color-primary-element-light);
	border: 2px dashed var(--color-primary-element);
	border-radius: var(--border-radius-large);
	min-height: 80px;
	margin: $stack-gap 0;
}
</style>
