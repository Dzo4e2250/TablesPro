# Nextcloud Tables - Upstream Issues za TablesPro

Pregled vseh odprtih issues iz [nextcloud/tables](https://github.com/nextcloud/tables/issues) za potencialno implementacijo v TablesPro fork.

**Datum analize:** 2025-12-28
**Skupno odprtih issues:** ~300

---

## PRIORITETA 1: Performance Issues (Kritično)

| # | Issue | Opis | Težavnost |
|---|-------|------|-----------|
| **#2158** | Performance Issues with Tables on Large Datasets | Počasno nalaganje velikih tabel | Visoka |
| **#1490** | Cannot open a table with 25k rows | Tabele z 25k vrsticami se ne odprejo | Visoka |
| **#2174** | Inline rich text rendering has performance issues | Rich text upočasnjuje renderiranje | Srednja |
| **#1864** | Use initial state for performance | Uporaba initial state za hitrejše nalaganje | Srednja |
| **#941** | Paginated row listing | Paginacija za boljšo performance | Visoka |
| **#1509** | Switch to infinite scrolling | Neskončno scrollanje | Srednja |
| **#1508** | Limit fetching of rows to a certain threshold | Omeji fetch na prag | Srednja |
| **#1507** | Row fetch endpoint with filters and sort | API za filtriranje na serverju | Visoka |

---

## PRIORITETA 2: UX/UI Bugs (Vidni uporabnikom)

| # | Issue | Opis | Težavnost |
|---|-------|------|-----------|
| **#2083** | Table title should not move when scrolling horizontally | Naslov tabele se premika pri scrollu | Nizka |
| **#2032** | Column text is overlapping | Besedilo stolpcev se prekriva | Srednja |
| **#2178** | "Save" button displaced | Gumb "Save" na napačnem mestu | Nizka |
| **#2087** | Mandatory-Icon too bright | Ikona za obvezna polja preveč svetla | Nizka |
| **#1414** | Pop-up not adapted to mobile | Modal slabo prilagojen mobilnim | Srednja |
| **#1439** | PDF Print does not respect content height | PDF tiskanje ne upošteva višine | Srednja |
| **#2230** | User filter is confusing | Uporabniški filter zmeden | Srednja |

---

## PRIORITETA 3: Manjkajoče Funkcionalnosti (Enhancement - Visoka vrednost)

### 3.1 Pogledi (Views)

| # | Issue | Opis | Že v TablesPro? |
|---|-------|------|-----------------|
| **#2229** | Reorder views by drag & drop | Preureditev pogledov z vlečenjem | NE |
| **#1683** | Possibility to order the views | Urejanje vrstnega reda pogledov | NE |
| **#1118** | Add rearranging views in the list | Preureditev pogledov v seznamu | NE |
| **#606** | Add summarization for view column | Povzetki za stolpce v pogledu | **DA (implementirano)** |
| **#313** | Add view mode: Tiles | Ploščice pogled | NE |
| **#312** | Add view mode: list | Seznam pogled | NE |
| **#362** | Add view mode: Blog | Blog pogled | NE |
| **#1454** | List views in tab based layout | Pogledi kot zavihki | NE |

### 3.2 Stolpci in Podatki

| # | Issue | Opis | Že v TablesPro? |
|---|-------|------|-----------------|
| **#2085** | Resize columns with drag handles | Spreminjanje širine stolpcev | **DA (implementirano)** |
| **#607** | Reorder columns | Preureditev stolpcev | Delno |
| **#1794** | Lookup columns (search in other tables) | Povezani stolpci med tabelami | NE |
| **#360** | Conditional formatting | Pogojno oblikovanje | NE |
| **#888** | Rudimentary Math | Osnovne matematične operacije | NE |
| **#580** | Column types File/Image | Tipi stolpcev za datoteke/slike | NE |
| **#402** | Column type IP-Address | Tip stolpca IP naslov | NE |
| **#2159** | Contact list column type | Tip stolpca za kontakte | NE |
| **#1884** | Column type user attribute | Tip stolpca za atribute uporabnika | NE |
| **#1721** | Column type 'Description' | Tip stolpca za opis | NE |

### 3.3 Vrstice (Rows)

| # | Issue | Opis | Že v TablesPro? |
|---|-------|------|-----------------|
| **#2093** | Collapsible table / row | Zložljive vrstice | **Planirano (Row Groups)** |
| **#1388** | Child items | Podrejeni elementi | NE |
| **#1461** | Insert row somewhere in existing table | Vstavljanje vrstice na določeno mesto | NE |
| **#2035** | Append to cell | Dodajanje k celici | NE |
| **#601** | Allow viewing/editing of own entries only | Ogled/urejanje samo svojih vnosov | NE |

### 3.4 Filtriranje in Sortiranje

| # | Issue | Opis |
|---|-------|------|
| **#1804** | Filter view for empty selection values | Filter za prazne selekcije |
| **#1826** | Add UNIQUE filter option | UNIQUE filter |
| **#1662** | Sort view by date day and month only | Sortiranje po dnevu/mesecu |
| **#600** | Time offset in views filter | Časovni zamik v filtru |
| **#920** | Autocomplete of fields from filters | Samodejno dopolnjevanje filtrov |

### 3.5 Import/Export

| # | Issue | Opis |
|---|-------|------|
| **#2173** | Table export different format than import expects | Neskladje med export/import formati |
| **#1853** | Add update function when importing | Posodobitev pri importu |
| **#1851** | Merge when importing | Združevanje pri importu |
| **#1436** | Export CSV should respect selected lines | Export izbrane vrstice |
| **#1412** | Export CSV with local adjustment | Export s časovnim pasom |
| **#378** | Copy from / paste to spreadsheets | Kopiraj/prilepi iz preglednic |

---

## PRIORITETA 4: API Improvements

| # | Issue | Opis |
|---|-------|------|
| **#2237** | v2 API: Adding new row is hard to figure out | Dokumentacija za dodajanje vrstic |
| **#2185** | Creating Selection Column via API fails | API za selekcijske stolpce ne dela |
| **#1856** | Cannot update view through API v1 | Posodobitev pogleda preko API |
| **#1840** | Expose columns through technical name/slug | Stolpci preko tehničnega imena |
| **#713** | Replace API v1 with v2 | Zamenjaj API v1 z v2 |
| **#918** | OCS-API for columns not working | OCS API za stolpce |
| **#1048** | `data` field empty at GET row | Prazen `data` pri GET vrstice |

---

## PRIORITETA 5: Sharing & Permissions

| # | Issue | Opis |
|---|-------|------|
| **#2180** | Permission problem - User should see view but doesn't | Napaka pri dovoljenjih |
| **#1854** | Let views be shared when table is shared | Deljenje pogledov ob tabeli |
| **#1825** | Permission for linked resources in shared tables | Dovoljenja za povezane vire |
| **#1682** | Recipient does not receive table group share | Prejemnik ne dobi deljene tabele |
| **#605** | Admin option for who can create tables | Admin nastavitev kdo lahko ustvarja |

---

## PRIORITETA 6: Technical Debt

| # | Issue | Opis |
|---|-------|------|
| **#1740** | Replace deprecated getQueryPart() | Zamenjaj zastarelo metodo |
| **#720** | Adjust testmatrix | Posodobi testno matriko |
| **#717** | Bring search e2e tests back | Vrni e2e teste za iskanje |
| **#386** | Add occ tests | Dodaj occ teste |
| **#1379** | Overhaul wiki documentation | Prenovi wiki dokumentacijo |

---

## PRIORITETA 7: Manjše Izboljšave (Nice to have)

| # | Issue | Opis |
|---|-------|------|
| **#1885** | Show progress bar percentage | Prikaži odstotek progress bara |
| **#603** | Add plus and minus buttons for counter | +/- gumbi za števec |
| **#1224** | Hide Pre-/Suffix for empty Values | Skrij prefix/suffix za prazne |
| **#1258** | Created by / last edited by - use displayname | Uporabi prikazno ime |
| **#1269** | Paste formatted DateTime value | Prilepi formatirano datum/čas |
| **#1714** | Datepicker with seconds values | Datepicker s sekundami |
| **#905** | Make date format shorter or adjustable | Krajši/nastavljiv format datuma |
| **#1380** | Show usergroup as username or id | Prikaži usergroup kot ime ali id |
| **#2082** | Tone down mandatory field warning | Umiri opozorilo za obvezna polja |

---

## ŽE IMPLEMENTIRANO V TABLESPRO

| Funkcionalnost | Upstream Issue | Status |
|---------------|----------------|--------|
| Column resize (drag) | #2085 | ✅ Implementirano |
| Summary rows (SUM, AVG, etc.) | #606 | ✅ Implementirano |
| Compact row design | N/A | ✅ Implementirano |
| Progress bars in summaries | N/A | ✅ Implementirano |
| Item count display | N/A | ✅ Implementirano |
| Row-level activity tracking | N/A | ✅ Implementirano |
| Board View (Kanban) | N/A | ✅ Implementirano |

---

## PREDLAGANE NASLEDNJE PRIORITETE ZA TABLESPRO

### Kratkoročno (Hitri win-i)
1. **#2083** - Fiksni naslov tabele pri scrollu (nizka težavnost, visoka vidnost)
2. **#1885** - Prikaži odstotek pri progress bar (good first issue)
3. **#603** - +/- gumbi za števec (good first issue)
4. **#1224** - Skrij prefix/suffix za prazne vrednosti (good first issue)

### Srednjeročno (Večja vrednost)
1. **#2229 + #1683** - Preureditev pogledov z drag & drop
2. **#1804** - Filter za prazne selekcije
3. **#360** - Pogojno oblikovanje (conditional formatting)
4. **#1794** - Lookup stolpci (povezave med tabelami)

### Dolgoročno (Kompleksne funkcionalnosti)
1. **Performance** - #941, #1509, #1507 - Paginacija in infinite scroll
2. **#313, #312** - Dodatni načini pogleda (tiles, list)
3. **#888** - Matematične operacije v stolpcih
4. **#580** - File/Image stolpci

---

## OPOMBE

- Issues označeni z "1. to develop" v upstreamu so pripravljeni za razvoj
- Issues označeni z "good first issue" so primerni za začetnike
- Nekatere issues so označene z "needs info" - potrebujejo več informacij
- Pri implementaciji vedno preveri ali je bug še vedno prisoten v najnovejši verziji
