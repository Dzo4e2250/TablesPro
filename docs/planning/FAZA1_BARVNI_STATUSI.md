# FAZA 1: Barvni statusi

## Cilj
Dodati možnost izbire barve za vsako opcijo v selection/dropdown stolpcu.

## Vizualni primer

```
┌─────────────────────────────────────────┐
│ Status                                  │
├─────────────────────────────────────────┤
│ [████ PLAČANO    ]                      │  ← Zelena
│ [████ V ČAKANJU  ]                      │  ← Oranžna
│ [████ ZAVRNJENO  ]                      │  ← Rdeča
│ [████ V OBDELAVI ]                      │  ← Modra
└─────────────────────────────────────────┘
```

## Datoteke za spremembo

### 1. SelectionForm.vue
**Pot:** `src/shared/components/ncTable/partials/columnTypePartials/forms/SelectionForm.vue`

**Kaj spremeniti:**
- Dodaj `<input type="color">` ali NcColorPicker poleg vsakega label inputa
- Posodobi `loadDefaultOptions()` da vključuje barve
- Posodobi `addOption()` da doda privzeto barvo

**Trenutna koda (vrstica 11-18):**
```vue
<div v-for="opt in mutableColumn.selectionOptions" :key="opt.id" class="col-4 inline">
  <NcCheckboxRadioSwitch :value="'' + opt.id" type="radio" :checked.sync="mutableColumn.selectionDefault" />
  <input :value="opt.label" @input="updateLabel(opt.id, $event)">
  <NcButton type="tertiary" @click="deleteOption(opt.id)">
    <template #icon>
      <DeleteOutline :size="20" />
    </template>
  </NcButton>
</div>
```

**Nova koda:**
```vue
<div v-for="opt in mutableColumn.selectionOptions" :key="opt.id" class="col-4 inline option-row">
  <NcCheckboxRadioSwitch :value="'' + opt.id" type="radio" :checked.sync="mutableColumn.selectionDefault" />
  <input
    type="color"
    :value="opt.color || '#607D8B'"
    class="color-picker"
    @input="updateColor(opt.id, $event)"
  >
  <input :value="opt.label" @input="updateLabel(opt.id, $event)">
  <NcButton type="tertiary" @click="deleteOption(opt.id)">
    <template #icon>
      <DeleteOutline :size="20" />
    </template>
  </NcButton>
</div>
```

**Dodaj metodo:**
```javascript
updateColor(id, e) {
  const i = this.mutableColumn.selectionOptions.findIndex((obj) => obj.id === id)
  const tmp = this.mutableColumn.selectionOptions
  tmp[i].color = e.target.value
  this.mutableColumn.selectionOptions = tmp
},
```

**Posodobi loadDefaultOptions():**
```javascript
loadDefaultOptions() {
  return [
    { id: 0, label: t('tablespro', 'First option'), color: '#4CAF50' },
    { id: 1, label: t('tablespro', 'Second option'), color: '#2196F3' },
  ]
},
```

**Posodobi addOption():**
```javascript
addOption() {
  const nextId = this.getNextId()
  this.mutableColumn.selectionOptions.push({
    id: nextId,
    label: '',
    color: '#607D8B', // Privzeta siva
  })
},
```

---

### 2. TableCellSelection.vue
**Pot:** `src/shared/components/ncTable/partials/TableCellSelection.vue`

**Kaj spremeniti:**
- V `non-edit-mode` prikaži barvni badge
- Dodaj computed za pridobitev barve trenutne vrednosti

**Trenutna koda (vrstica 6-9):**
```vue
<div v-if="!isEditing" class="non-edit-mode" @click="handleStartEditing">
  {{ column.getLabel(value) }}<span v-if="isDeleted()" :title="t('tablespro', 'This option is outdated.')">&nbsp;⚠️</span>
</div>
```

**Nova koda:**
```vue
<div v-if="!isEditing" class="non-edit-mode" @click="handleStartEditing">
  <span
    v-if="value !== null"
    class="status-badge"
    :style="{
      backgroundColor: getOptionColor,
      color: getContrastColor(getOptionColor)
    }"
  >
    {{ column.getLabel(value) }}
  </span>
  <span v-else class="empty-value">-</span>
  <span v-if="isDeleted()" :title="t('tablespro', 'This option is outdated.')">&nbsp;⚠️</span>
</div>
```

**Dodaj computed:**
```javascript
computed: {
  // ... obstoječe
  getOptionColor() {
    const option = this.getOptionObject(this.value)
    return option?.color || '#607D8B'
  },
},
```

**Dodaj metodo za kontrast:**
```javascript
methods: {
  // ... obstoječe
  getContrastColor(hexColor) {
    if (!hexColor) return '#000000'
    const hex = hexColor.replace('#', '')
    const r = parseInt(hex.substr(0, 2), 16)
    const g = parseInt(hex.substr(2, 2), 16)
    const b = parseInt(hex.substr(4, 2), 16)
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
    return luminance > 0.5 ? '#000000' : '#FFFFFF'
  },
},
```

**Dodaj CSS:**
```scss
.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
}

.empty-value {
  color: var(--color-text-maxcontrast);
}
```

---

### 3. TableCellMultiSelection.vue
**Pot:** `src/shared/components/ncTable/partials/TableCellMultiSelection.vue`

Podobne spremembe kot za TableCellSelection, ampak za več badge-ov.

---

## Privzete barve

```javascript
// src/shared/constants/colors.js (nova datoteka)
export const DEFAULT_STATUS_COLORS = {
  green: '#4CAF50',   // Odobreno, Done, Plačano
  red: '#F44336',     // Zavrnjeno, Blocked, Preklicano
  orange: '#FF9800',  // V čakanju, Pending
  blue: '#2196F3',    // V obdelavi, In Progress
  purple: '#9C27B0',  // Review, Na pregledu
  gray: '#607D8B',    // Draft, Osnutek
  cyan: '#00BCD4',    // Info
  yellow: '#FFEB3B',  // Opozorilo, Warning
}

export const COLOR_PRESETS = [
  '#4CAF50', '#8BC34A', '#CDDC39',  // Zelene
  '#F44336', '#E91E63', '#FF5722',  // Rdeče/Roza
  '#FF9800', '#FFC107', '#FFEB3B',  // Oranžne/Rumene
  '#2196F3', '#03A9F4', '#00BCD4',  // Modre
  '#9C27B0', '#673AB7', '#3F51B5',  // Vijolične
  '#607D8B', '#9E9E9E', '#795548',  // Sive/Rjave
]
```

---

## Checklist

- [ ] Ustvari `src/shared/constants/colors.js`
- [ ] Posodobi `SelectionForm.vue` - dodaj color picker
- [ ] Dodaj `updateColor()` metodo
- [ ] Posodobi `loadDefaultOptions()` z barvami
- [ ] Posodobi `addOption()` z privzeto barvo
- [ ] Posodobi `TableCellSelection.vue` - barvni badge
- [ ] Dodaj `getContrastColor()` utility
- [ ] Posodobi `TableCellMultiSelection.vue`
- [ ] Dodaj CSS za badge elemente
- [ ] Build in test
- [ ] Git commit

---

## Testiranje

1. Ustvari nov stolpec tipa "Selection"
2. Preveri da imajo opcije color picker
3. Spremeni barve opcij
4. Preveri da se barve prikažejo v tabeli
5. Preveri kontrast teksta (bela na temni, črna na svetli)
6. Preveri multi-selection prikaz
