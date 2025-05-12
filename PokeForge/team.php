<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "poke_forge");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Recupero lista pokemon base
$pokemon_stmt = $conn->prepare("SELECT ID, nome, tipo1, tipo2, sprite_url FROM Poke_Esistenti ORDER BY nome ASC");
$pokemon_stmt->execute();
$pokemon_list = $pokemon_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pokemon_stmt->close();

// Recupero lista mosse complete
$mosse_stmt = $conn->prepare("SELECT ID, nome FROM Mosse ORDER BY nome ASC");
$mosse_stmt->execute();
$mosse_list = $mosse_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$mosse_stmt->close();

// Recupero lista mosse-Pokemon
$pokemon_mosse_stmt = $conn->prepare("SELECT ID_Pokemon, ID_Mossa FROM Pokemon_Mosse");
$pokemon_mosse_stmt->execute();
$pokemon_mosse_result = $pokemon_mosse_stmt->get_result();
$pokemon_mosse_data = [];
while ($row = $pokemon_mosse_result->fetch_assoc()) {
    $pokemon_mosse_data[(int)$row['ID_Pokemon']][] = (int)$row['ID_Mossa'];
}
$pokemon_mosse_stmt->close();

// Recupero lista abilità associate a ogni Pokémon
$pokemon_abilita_stmt = $conn->prepare("SELECT ID_Pokemon, nome_abilita FROM Pokemon_Abilita");
$pokemon_abilita_stmt->execute();
$pokemon_abilita_result = $pokemon_abilita_stmt->get_result();
$pokemon_abilita_data = [];
while ($row = $pokemon_abilita_result->fetch_assoc()) {
    $pokemon_abilita_data[(int)$row['ID_Pokemon']][] = $row['nome_abilita'];
}
$pokemon_abilita_stmt->close();

// Recupero lista tipi
$tipi_stmt = $conn->prepare("SELECT nome FROM Tipi");
$tipi_stmt->execute();
$tipi_result = $tipi_stmt->get_result();
$tipi_list = array_column($tipi_result->fetch_all(MYSQLI_ASSOC), 'nome');
$tipi_stmt->close();

// Carico team se esistente
$team_id = $_GET['team_id'] ?? null;
$poke_team_data = [];
$nome_team = "";
if ($team_id) {
    $stmt = $conn->prepare("
        SELECT pt.*, p.nome as nome_pokemon, p.tipo1, p.tipo2, p.sprite_url 
        FROM Poke_Team pt
        JOIN Poke_Esistenti p ON pt.ID_Pokemon = p.ID
        WHERE pt.ID_Team = ?
        ORDER BY pt.ID ASC
    ");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $poke_team_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt2 = $conn->prepare("SELECT nomeTeam FROM Team WHERE ID = ? AND ID_Utente = ?");
    $stmt2->bind_param("ii", $team_id, $_SESSION['user_id']);
    $stmt2->execute();
    if ($row2 = $stmt2->get_result()->fetch_assoc()) {
        $nome_team = $row2['nomeTeam'];
    }
    $stmt2->close();
}

$conn->close();

function selectOptions($items, $selectedValue, $textKey = "nome", $valueKey = "ID") {
    $html = "<option value=''>-- Seleziona --</option>";
    foreach ($items as $item) {
        $val = $item[$valueKey] ?? $item;
        $text = $item[$textKey] ?? $item;
        $sel = ($val == $selectedValue) ? "selected" : "";
        $html .= "<option value='" . htmlspecialchars($val) . "' $sel>" . htmlspecialchars($text) . "</option>";
    }
    return $html;
}

$nature_list = [
    'Adamant', 'Bashful', 'Bold', 'Brave', 'Calm', 'Careful', 'Docile', 'Gentle',
    'Hardy', 'Hasty', 'Impish', 'Jolly', 'Lax', 'Lonely', 'Mild', 'Modest',
    'Naive', 'Naughty', 'Quiet', 'Quirky', 'Rash', 'Relaxed', 'Sassy', 'Serious',
    'Timid'
];

$items_list = [
    "Abomasite", "Absorb Bulb", "Adamant Orb", "Air Balloon", "Amulet Coin", "Assault Vest", "Berry Juice",
    "Bright Powder", "Cheri Berry", "Chesto Berry", "Choice Band", "Choice Scarf", "Choice Specs",
    "Coba Berry", "Colbur Berry", "Custap Berry", "Deep Sea Scale", "Deep Sea Tooth", "Eject Button",
    "Expert Belt", "Focus Band", "Focus Sash", "Full Incense", "Heat Rock", "Heavy Duty Boots",
    "Hondew Berry", "Iapapa Berry", "Insect Plate", "Iron Ball", "Jaboca Berry", "Kasib Berry",
    "Kebia Berry", "Lagging Tail", "Leftovers", "Liechi Berry", "Life Orb", "Light Ball", "Lum Berry",
    "Mental Herb", "Metronome", "Micle Berry", "Muscle Band", "Never-Melt Ice", "Occa Berry", "Oval Stone",
    "Passho Berry", "Payapa Berry", "Persim Berry", "Petaya Berry", "Power Anklet", "Power Band",
    "Power Belt", "Power Bracer", "Power Lens", "Power Weight", "Quick Claw", "Red Card", "Ring Target",
    "Rock Incense", "Rose Incense", "Salac Berry", "Scope Lens", "Sharp Beak", "Shell Bell", "Shuca Berry",
    "Sitrus Berry", "Sky Plate", "Soft Sand", "Spell Tag", "Sticky Barb", "Tanga Berry", "Toxic Orb",
    "Twisted Spoon", "Weakness Policy", "Wide Lens", "Wise Glasses", "Yache Berry"
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Gestisci Team - PokeForge</title>
<style>
body { font-family: Arial,sans-serif; background: #f8f8f8; margin:0; padding:10px;}
.container { max-width:1200px; margin:auto; background:#fff; padding:20px; box-shadow:0 0 10px #ccc; border-radius:8px;}
h2 { text-align:center; color:#cc0000; margin-bottom:20px; }
.flex-row { display:flex; gap:20px; }
.pokemon-selection { flex:3; }
.coverage-table { flex:1; border-collapse:collapse; }
.coverage-table th, .coverage-table td { border:1px solid #ccc; padding:5px; text-align:center; font-size:14px; }
.pokemon-slot { display:flex; align-items:center; margin-bottom:15px; gap:10px; }
.pokemon-slot img { width:64px; height:64px; border:1px solid #ccc; border-radius:8px; }
select, button { padding:6px; font-size:14px; }
.attributes { display:flex; gap:10px; flex-wrap:wrap; margin-top:6px;}
.attributes select { flex:1; min-width:140px;}
#suggestions { margin-top:20px; background:#ffecec; padding:10px; border-radius:8px; max-height:150px; overflow-y:auto; display:none;}
.btn-row { margin-top:20px; display:flex; gap:10px; justify-content:center;}
.btn-row button { flex:1; font-weight:bold; cursor:pointer; border-radius:6px; border:none; padding:10px;}
.btn-save { background-color:#28a745; color:#fff;}
.btn-back { background-color:#6c757d; color:#fff;}
.btn-suggest { background-color:#007bff; color:#fff;}
</style>
</head>
<body>
<div class="container">
<h2>Gestisci Team: <?php echo htmlspecialchars($nome_team ?: "Nuovo Team"); ?></h2>
<form id="teamForm" method="post" action="team_save.php">
<input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id ?: ''); ?>" />
<div class="flex-row">
<div class="pokemon-selection">
<?php for ($i=0; $i < 6; $i++):
    $slot = $poke_team_data[$i] ?? [];
    $selectedPokemonID = $slot['ID_Pokemon'] ?? '';
    $sprite = isset($slot['sprite']) && !empty($slot['sprite']) ? $slot['sprite'] : (isset($slot['sprite_url']) ? $slot['sprite_url'] : 'https://via.placeholder.com/64?text=?');
    $abilita = $slot['abilita'] ?? '';
    $natura = $slot['natura'] ?? '';
    $oggetto = $slot['oggetto'] ?? '';
    $mossa1 = $slot['mossa1'] ?? '';
    $mossa2 = $slot['mossa2'] ?? '';
    $mossa3 = $slot['mossa3'] ?? '';
    $mossa4 = $slot['mossa4'] ?? '';
?>
<div class="pokemon-slot" data-slot="<?php echo $i; ?>">
    <div>
        <label>Pokémon <?php echo ($i+1); ?></label><br />
        <select name="pokemon[<?php echo $i; ?>][ID_Pokemon]" class="pokemon-select" data-slot="<?php echo $i; ?>">
            <option value="">-- Seleziona Pokémon --</option>
            <?php foreach ($pokemon_list as $pk): ?>
                <option value="<?php echo $pk['ID']; ?>" <?php echo ($pk['ID'] == $selectedPokemonID) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($pk['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <img src="<?php echo htmlspecialchars($sprite); ?>" alt="Sprite" class="pokemon-sprite" id="sprite-<?php echo $i; ?>" />
    <div class="attributes">
        <select name="pokemon[<?php echo $i; ?>][abilita]" class="abilita-select" data-slot="<?php echo $i; ?>">
            <option value="">Abilità</option>
            <?php
                $abilitiesForSlot = $pokemon_abilita_data[$selectedPokemonID] ?? $abilities_list;
                foreach ($abilitiesForSlot as $abil){
                    $sel = ($abil == $abilita) ? 'selected' : '';
                    echo '<option value="'.htmlspecialchars($abil).'" '.$sel.'>'.htmlspecialchars($abil).'</option>';
                }
            ?>
        </select>
        <select name="pokemon[<?php echo $i; ?>][natura]">
            <option value="">Natura</option>
            <?php foreach ($nature_list as $nat): 
                $sel = ($nat == $natura) ? 'selected' : '';
            ?>
                <option value="<?php echo $nat; ?>" <?php echo $sel; ?>><?php echo $nat; ?></option>
            <?php endforeach; ?>
        </select>
        <select name="pokemon[<?php echo $i; ?>][oggetto]">
            <option value="">Oggetto</option>
            <?php foreach ($items_list as $item): 
                $sel = ($item == $oggetto) ? 'selected' : '';
            ?>
                <option value="<?php echo htmlspecialchars($item); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($item); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="attributes">
        <select name="pokemon[<?php echo $i; ?>][mossa1]" class="mossa-select" data-slot="<?php echo $i; ?>"></select>
        <select name="pokemon[<?php echo $i; ?>][mossa2]" class="mossa-select" data-slot="<?php echo $i; ?>"></select>
        <select name="pokemon[<?php echo $i; ?>][mossa3]" class="mossa-select" data-slot="<?php echo $i; ?>"></select>
        <select name="pokemon[<?php echo $i; ?>][mossa4]" class="mossa-select" data-slot="<?php echo $i; ?>"></select>
    </div>
</div>
<?php endfor; ?>
</div>
<div>
<h3>Coverage del Team (Difensivo)</h3>
<table class="coverage-table" id="coverageTable">
<thead>
<tr>
<th>Tipo</th>
<th>Debolezze</th>
<th>Resistenze</th>
</tr>
</thead>
<tbody>
<?php foreach($tipi_list as $tipo): ?>
<tr>
    <td><?php echo htmlspecialchars($tipo); ?></td>
    <td class="weakness-count" data-tipo="<?php echo htmlspecialchars($tipo); ?>">0</td>
    <td class="resistance-count" data-tipo="<?php echo htmlspecialchars($tipo); ?>">0</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
<div id="suggestions"></div>
<div class="btn-row">
<button type="button" class="btn-back" onclick="window.location.href='index.php'">Torna Indietro</button>
<button type="button" class="btn-suggest" id="btnSuggest">Suggerisci</button>
<button type="submit" class="btn-save">Salva Team</button>
</div>
</form>
</div>
<script>
const pokemonList = <?php echo json_encode($pokemon_list); ?>;
const mosseList = <?php echo json_encode($mosse_list); ?>;
const tipiList = <?php echo json_encode($tipi_list); ?>;
const pokemonMosseData = <?php echo json_encode($pokemon_mosse_data); ?>;
const pokemonAbilitaData = <?php echo json_encode($pokemon_abilita_data); ?>;

const pokemonMap = {};
pokemonList.forEach(p => {
    pokemonMap[p.ID] = p;
});

// Aggiorna sprite
function updateSprite(slot) {
    const select = document.querySelector(`select.pokemon-select[data-slot='${slot}']`);
    const img = document.getElementById(`sprite-${slot}`);
    const pokeID = select.value;
    img.src = (pokemonMap[pokeID] && pokemonMap[pokeID].sprite_url) ? pokemonMap[pokeID].sprite_url : 'https://via.placeholder.com/64?text=?';
}

// Carica mosse filtrate
function loadMovesForPokemon(pokemonID, slot) {
    const selects = document.querySelectorAll(`select.mossa-select[data-slot='${slot}']`);
    selects.forEach(sel => sel.innerHTML = '<option value="">-- Seleziona Mossa --</option>');
    if(!pokemonID || !pokemonMosseData[pokemonID]) return;
    pokemonMosseData[pokemonID].forEach(mossaID => {
        const mos = mosseList.find(m => m.ID == mossaID);
        if(mos) selects.forEach(sel => {
            const opt = document.createElement('option');
            opt.value = mos.ID;
            opt.textContent = mos.nome;
            sel.appendChild(opt);
        });
    });
}

// Carica abilità filtrate
function loadAbilitiesForPokemon(pokemonID, slot) {
    const select = document.querySelector(`select.abilita-select[data-slot='${slot}']`);
    select.innerHTML = '<option value="">Abilità</option>';
    if(!pokemonID || !pokemonAbilitaData[pokemonID]) return;
    pokemonAbilitaData[pokemonID].forEach(abilita => {
        const opt = document.createElement('option');
        opt.value = abilita;
        opt.textContent = abilita;
        select.appendChild(opt);
    });
}

// Mappa di efficacia dei tipi (danno ricevuto)
const typeEffectiveness = {
    "Normale":{"Roccia":0.5, "Spettro":0, "Acciaio":0.5},
    "Fuoco":{"Fuoco":0.5, "Acqua":0.5, "Erba":2, "Ghiaccio":2, "Coleottero":2, "Roccia":0.5, "Drago":0.5, "Acciaio":2},
    "Acqua":{"Fuoco":2, "Acqua":0.5, "Erba":0.5, "Terra":2, "Roccia":2, "Drago":0.5},
    "Erba":{"Fuoco":0.5, "Acqua":2, "Erba":0.5, "Veleno":0.5, "Terra":2, "Volante":0.5, "Coleottero":0.5, "Roccia":2, "Drago":0.5, "Acciaio":0.5},
    "Elettro":{"Acqua":2, "Erba":0.5, "Elettro":0.5, "Terra":0, "Volante":2, "Drago":0.5},
    "Ghiaccio":{"Fuoco":0.5, "Acqua":0.5, "Ghiaccio":0.5, "Acciaio":0.5, "Erba":2, "Terra":2, "Volante":2, "Drago":2},
    "Lotta":{"Veleno":0.5, "Volante":0.5, "Psico":0.5, "Ghiaccio":2, "Buio":2, "Acciaio":2, "Folletto":0.5},
    "Veleno":{"Veleno":0.5, "Terra":0.5, "Roccia":0.5, "Spettro":0.5, "Folletto":2},
    "Terra":{"Fuoco":2, "Elettro":2, "Erba":0.5, "Veleno":2, "Volante":0, "Roccia":2, "Acciaio":2},
    "Volante":{"Elettro":0.5, "Lotta":2, "Erba":2, "Roccia":0.5, "Acciaio":0.5},
    "Psico":{"Lotta":2, "Psico":0.5, "Buio":0, "Folletto":0.5},
    "Coleottero":{"Fuoco":0.5, "Lotta":0.5, "Terra":1, "Volante":0.5, "Psico":2, "Spettro":0.5, "Buio":2, "Acciaio":0.5, "Folletto":0.5},
    "Roccia":{"Fuoco":2, "Lotta":0.5, "Terra":0.5, "Volante":2, "Coleottero":2, "Acciaio":0.5},
    "Spettro":{"Normale":0, "Psico":2, "Spettro":2, "Buio":0.5},
    "Drago":{"Drago":2, "Acciaio":0.5, "Folletto":0},
    "Buio":{"Lotta":0.5, "Psico":2, "Spettro":2, "Folletto":0.5},
    "Acciaio":{"Fuoco":0.5, "Acqua":0.5, "Elettro":0.5, "Ghiaccio":2, "Roccia":2, "Folletto":2},
    "Folletto":{"Fuoco":0.5, "Lotta":2, "Veleno":0.5, "Drago":2, "Buio":2}
};

// Variabili globale caricate da PHP in JS (ricordati di esportarle con json_encode)
// esempio:
// const pokemonMap = {...};
// const tipiList = [...];

function aggiornaCoverageInTempoReale() {
    // inizializza contatori
    const weaknessCount = {};
    const resistanceCount = {};
    tipiList.forEach(tipo => {
        weaknessCount[tipo] = 0;
        resistanceCount[tipo] = 0;
    });

    for(let slot=0; slot<6; slot++) {
        const select = document.querySelector(`select.pokemon-select[data-slot='${slot}']`);
        if(!select) continue;
        const pokeID = select.value;
        if(!pokemonMap[pokeID]) continue;
        const poke = pokemonMap[pokeID];
        const tipo1 = poke.tipo1 || null;
        const tipo2 = poke.tipo2 || null;

        tipiList.forEach(attackingType => {
            const eff1 = tipo1 ? (typeEffectiveness[attackingType]?.[tipo1] ?? 1) : 1;
            const eff2 = tipo2 ? (typeEffectiveness[attackingType]?.[tipo2] ?? 1) : 1;
            const multiplier = eff1 * eff2;

            if(multiplier > 1) weaknessCount[attackingType]++;
            else if(multiplier > 0 && multiplier < 1) resistanceCount[attackingType]++;
        });
    }

    // Aggiorna tabella HTML
    document.querySelectorAll('.weakness-count').forEach(td => {
        const tipo = td.getAttribute('data-tipo');
        td.textContent = weaknessCount[tipo] || 0;
    });
    document.querySelectorAll('.resistance-count').forEach(td => {
        const tipo = td.getAttribute('data-tipo');
        td.textContent = resistanceCount[tipo] || 0;
    });
}

// Carica sprite
function updateSprite(slot) {
    const select = document.querySelector(`select.pokemon-select[data-slot='${slot}']`);
    const img = document.getElementById(`sprite-${slot}`);
    const pokeID = select.value;
    img.src = (pokemonMap[pokeID] && pokemonMap[pokeID].sprite_url) ? pokemonMap[pokeID].sprite_url : 'https://via.placeholder.com/64?text=?';
}

// Carica mosse filtrate per pokemon
function loadMovesForPokemon(pokemonID, slot) {
    const selects = document.querySelectorAll(`select.mossa-select[data-slot='${slot}']`);
    selects.forEach(sel => sel.innerHTML = '<option value="">-- Seleziona Mossa --</option>');
    if(!pokemonID || !pokemonMosseData[pokemonID]) return;
    pokemonMosseData[pokemonID].forEach(mossaID => {
        const mos = mosseList.find(m => m.ID == mossaID);
        if (mos) {
            selects.forEach(sel => {
                const opt = document.createElement('option');
                opt.value = mos.ID;
                opt.textContent = mos.nome;
                sel.appendChild(opt);
            });
        }
    });
}

// Funzione suggerimenti pokemon per coprire debolezze
function suggerisciPokemon() {
    const debolezze = {};
    tipiList.forEach(tipo => debolezze[tipo] = 0);

    document.querySelectorAll('.weakness-count').forEach(td => {
        const tipo = td.getAttribute('data-tipo');
        debolezze[tipo] = parseInt(td.textContent, 10) || 0;
    });

    // Trova max debolezza
    let maxDeb = 0;
    for (const tipo in debolezze) {
        if (debolezze[tipo] > maxDeb) maxDeb = debolezze[tipo];
    }
    if(maxDeb === 0){
        alert("Il team non presenta debolezze significative.");
        return;
    }

    const tipiDebolezze = Object.keys(debolezze).filter(k => debolezze[k] === maxDeb);

    // Cerca pokemon che resistono almeno a uno dei tipi forti
    let suggeriti = pokemonList.filter(p => {
        return tipiDebolezze.some(weakType => {
            const m1 = typeEffectiveness[weakType]?.[p.tipo1] ?? 1;
            const m2 = p.tipo2 ? (typeEffectiveness[weakType]?.[p.tipo2] ?? 1) : 1;
            const mult = m1 * m2;
            return mult < 1 && mult > 0;
        });
    });

    // Escludi già presenti
    let teamIDs = [];
    for(let i=0; i<6; i++){
        const sel = document.querySelector(`select.pokemon-select[data-slot='${i}']`);
        if(sel && sel.value) teamIDs.push(parseInt(sel.value));
    }
    suggeriti = suggeriti.filter(p => !teamIDs.includes(p.ID)).slice(0,10);

    const sugDiv = document.getElementById('suggestions');
    if(suggeriti.length === 0){
        sugDiv.style.display = 'block';
        sugDiv.innerHTML = '<strong>Nessun Pokémon suggerito disponibile per coprire le debolezze.</strong>';
        return;
    }

    let html = '<strong>Pokémon suggeriti per coprire le debolezze:</strong><ul>';
    suggeriti.forEach(p => {
        html += `<li><img src="${p.sprite_url}" alt="${p.nome}" width="32" height="32"/> ${p.nome}</li>`;
    });
    html += '</ul>';
    sugDiv.style.display = 'block';
    sugDiv.innerHTML = html;
}

window.addEventListener('load', () => {
    for(let i=0; i<6; i++){
        const select = document.querySelector(`select.pokemon-select[data-slot='${i}']`);
        if(select){
            select.addEventListener('change', () => {
                updateSprite(i);
                loadMovesForPokemon(select.value, i);
                aggiornaCoverageInTempoReale();
            });
            updateSprite(i);
            loadMovesForPokemon(select.value, i);
        }
    }
    aggiornaCoverageInTempoReale();

    document.getElementById('btnSuggest').addEventListener('click', suggerisciPokemon);
});

</script>
</body>
</html>
