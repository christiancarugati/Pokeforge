<?php
require_once("conn.php"); // Include il file di connessione al database

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Recupero lista pokemon base
$pokemon_stmt = $conn->prepare("SELECT ID, nome, tipo1, tipo2, sprite_url FROM Poke_Esistenti ORDER BY nome ASC");
$pokemon_stmt->execute();
$pokemon_result = $pokemon_stmt->get_result();
$pokemon_list = $pokemon_result->fetch_all(MYSQLI_ASSOC);
$pokemon_stmt->close();

// Recupero lista mosse complete
$mosse_stmt = $conn->prepare("SELECT ID, nome FROM Mosse ORDER BY nome ASC");
$mosse_stmt->execute();
$mosse_result = $mosse_stmt->get_result();
$mosse_list = $mosse_result->fetch_all(MYSQLI_ASSOC);
$mosse_stmt->close();

// Recupero lista mosse-Pokemon (per filtro mosse disponibili per ogni Pokémon)
$pokemon_mosse_stmt = $conn->prepare("SELECT ID_Pokemon, ID_Mossa FROM Pokemon_Mosse");
$pokemon_mosse_stmt->execute();
$pokemon_mosse_result = $pokemon_mosse_stmt->get_result();
$pokemon_mosse_data = [];
while ($row = $pokemon_mosse_result->fetch_assoc()) {
    $pokemon_mosse_data[(int)$row['ID_Pokemon']][] = (int)$row['ID_Mossa'];
}
$pokemon_mosse_stmt->close();

// Recupero elenco abilità per ogni Pokémon
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

// Vettore oggetti assegnabili
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

// Se ID team fornito, carico team e suoi pokemon
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
    $res = $stmt->get_result();
    $poke_team_data = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Recupero nome team
    $stmt2 = $conn->prepare("SELECT nomeTeam FROM Team WHERE ID = ? AND ID_Utente = ?");
    $stmt2->bind_param("ii", $team_id, $_SESSION['user_id']);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($row2 = $res2->fetch_assoc()) {
        $nome_team = $row2['nomeTeam'];
    }
    $stmt2->close();
}

$conn->close();

function selectOptions($items, $selectedValue, $textKey = "nome", $valueKey = "ID") {
    $html = "<option value=''>-- Seleziona --</option>";
    foreach ($items as $item) {
        $val = $item[$valueKey];
        $text = $item[$textKey];
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
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Gestisci Team - PokeForge</title>
<style>
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8f8f8; 
        margin: 0; 
        padding: 10px; 
    }
    .container { 
        max-width: 1200px; 
        margin: auto; 
        background: #fff; 
        padding: 20px; 
        box-shadow: 0 0 10px #ccc; 
        border-radius: 8px; 
    }
    h2 { 
        text-align: center; 
        margin-bottom: 20px; 
        color: #cc0000; 
    }
    .flex-row { 
        display: flex; 
        gap: 20px; 
    }
    .pokemon-selection { 
        flex: 3; 
    }
    .coverage-table { 
        width: 100%;
        border-collapse: collapse; 
        background: white; 
        box-shadow: 0 4px 12px rgba(26,62,154,0.2);
        border-radius: 8px; 
        overflow: hidden; 
    }
    .coverage-table th, .coverage-table td { 
        padding: 14px 20px; 
        border-bottom: 1px solid #ddd; 
        text-align: center; 
    }
    .coverage-table th { 
        background: #cc0000; 
        color: white; 
        font-weight: 700;
        font-size: 1.1rem;
    }
    .coverage-table tbody tr:hover {
        background-color: #fff0f0;
    }
    .pokemon-slot { 
        display: flex; 
        align-items: center; 
        margin-bottom: 15px; 
        gap: 10px; 
    }
    .pokemon-slot img { 
        width: 64px; 
        height: 64px; 
        border: 1px solid #ccc; 
        border-radius: 8px; 
    }
    select, button { 
        padding: 6px; 
        font-size: 14px; 
        width: 100%; 
        max-width: 320px; 
    }
    select {
        border-radius: 6px;
        border: 2px solid #cc0000;
        padding: 8px;
        transition: border-color 0.3s ease;
    }
    select:hover, select:focus {
        border-color: #aa0000;
        outline: none;
    }
    .attributes { 
        display: flex; 
        gap: 10px; 
        flex-wrap: wrap; 
        margin-top: 6px; 
    }
    .attributes select { 
        flex: 1; 
        min-width: 140px; 
    }
    #suggestions { 
        margin-top: 20px; 
        background: #ffecec; 
        padding: 10px; 
        border-radius: 8px; 
        max-height: 150px; 
        overflow-y: auto; 
        display: none; 
    }
    .btn-row { 
        margin-top: 20px; 
        display: flex; 
        gap: 10px; 
        justify-content: center; 
    }
    .btn-row button { 
        flex: 1; 
        font-weight: bold; 
        cursor: pointer; 
        border-radius: 6px; 
        border: none; 
        padding: 10px; 
    }
    .btn-save { 
        background-color: #28a745; 
        color: white; 
    }
    .btn-back { 
        background-color: #6c757d; 
        color: white; 
    }
    .btn-suggest { 
        background-color: #007bff; 
        color: white; 
    }
    .color-red { 
        color: #cc0000; 
        font-weight:bold; 
    }
    .color-green { 
        color: green; 
        font-weight:bold; 
    }
    .color-yellow { 
        color: #bb8800; 
        font-weight:bold; 
    }
    .multiplier-high { 
        color: #d32f2f; 
        font-weight: 700; 
    }
    .multiplier-normal { 
        color: #555; 
    }
    .multiplier-low { 
        color: #2e7d32; 
        font-weight: 600; 
    }
    .type-cell {
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.04em;
    }
    .weakness-analysis {
        flex: 1;
    }
</style>
</head>
<script>
const allTypes = [
    "normal", "fire", "water", "electric", "grass", "ice", "fighting", "poison", "ground",
    "flying", "psychic", "bug", "rock", "ghost", "dragon", "dark", "steel", "fairy"
];

async function getTypeEffectiveness(typeName) {
    const res = await fetch(`https://pokeapi.co/api/v2/type/${typeName}`);
    const data = await res.json();

    const effectiveness = {};
    allTypes.forEach(t => effectiveness[t] = 1);

    data.damage_relations.double_damage_from.forEach(t => effectiveness[t.name] *= 2);
    data.damage_relations.half_damage_from.forEach(t => effectiveness[t.name] *= 0.5);
    data.damage_relations.no_damage_from.forEach(t => effectiveness[t.name] *= 0);

    return effectiveness;
}

async function updateTeamDefensiveCoverage() {
    const selectedPokemon = Array.from(document.querySelectorAll('select[name^="pokemon"]'))
        .map(select => select.value)
        .filter(name => name);

    const teamMultipliers = {};
    allTypes.forEach(t => teamMultipliers[t] = []);

    for (const name of selectedPokemon) {
        const res = await fetch(`https://pokeapi.co/api/v2/pokemon/${name.toLowerCase()}`);
        const data = await res.json();
        const pokemonTypes = data.types.map(t => t.type.name);

        const effectivenessPerType = {};
        for (const attackType of allTypes) {
            effectivenessPerType[attackType] = 1;
            for (const pType of pokemonTypes) {
                const eff = await getTypeEffectiveness(pType);
                effectivenessPerType[attackType] *= eff[attackType];
            }
        }

        for (const tipo of allTypes) {
            teamMultipliers[tipo].push(effectivenessPerType[tipo]);
        }
    }

    for (const tipo of allTypes) {
        const values = teamMultipliers[tipo];
        if (values.length === 0) continue;
        const avg = values.reduce((a, b) => a + b, 0) / values.length;
        const rounded = Math.round(avg * 10) / 10;

        const cell = document.querySelector(`td[data-debolezza="${tipo}"]`);
        if (cell) {
            cell.textContent = `${rounded}×`;
            if (rounded > 1) {
                cell.style.backgroundColor = "#f8d7da";
            } else if (rounded < 1) {
                cell.style.backgroundColor = "#d4edda";
            } else {
                cell.style.backgroundColor = "#ffffff";
            }
        }
    }
}
</script>
<body>
<div class="container">
    <h2>Gestisci Team: <?php echo htmlspecialchars($nome_team ?: 'Nuovo Team'); ?></h2>
    <form id="teamForm" method="post" action="team_save.php">
        <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id ?: ''); ?>" />
        <div class="flex-row">
            <div class="pokemon-selection">
                <?php for ($i=0; $i<6; $i++):
                    $slot = $poke_team_data[$i] ?? [];
                    $selectedPokemonID = $slot['ID_Pokemon'] ?? '';
                    $sprite = $slot['sprite_url'] ?? 'https://via.placeholder.com/64?text=?';
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
                        <label>Pokémon <?php echo $i+1; ?></label><br />
                        <select name="pokemon[<?php echo $i; ?>][ID_Pokemon]" class="pokemon-select" data-slot="<?php echo $i; ?>">
                            <option value="">-- Seleziona Pokémon --</option>
                            <?php foreach ($pokemon_list as $p): ?>
                                <option value="<?php echo $p['ID']; ?>" <?php echo ($p['ID'] == $selectedPokemonID) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <img src="<?php echo htmlspecialchars($sprite); ?>" alt="Sprite" class="pokemon-sprite" id="sprite-<?php echo $i; ?>" />
                    <div class="attributes">
                        <select name="pokemon[<?php echo $i; ?>][abilita]" class="abilita-select" data-slot="<?php echo $i; ?>">
                            <option value="">Abilità</option>
                        </select>
                        <select name="pokemon[<?php echo $i; ?>][natura]">
                            <option value="">Natura</option>
                            <?php foreach ($nature_list as $nat): ?>
                                <option value="<?php echo $nat; ?>" <?php echo ($nat == $natura) ? 'selected' : ''; ?>><?php echo $nat; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="pokemon[<?php echo $i; ?>][oggetto]">
                            <option value="">Oggetto</option>
                            <?php foreach ($items_list as $item): ?>
                                <option value="<?php echo htmlspecialchars($item); ?>" <?php echo ($item == $oggetto) ? 'selected' : ''; ?>><?php echo htmlspecialchars($item); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="attributes">
                        <select name="pokemon[<?php echo $i; ?>][mossa1]" class="mossa-select" data-slot="<?php echo $i; ?>" data-current-value="<?php echo htmlspecialchars($mossa1); ?>"></select>
                        <select name="pokemon[<?php echo $i; ?>][mossa2]" class="mossa-select" data-slot="<?php echo $i; ?>" data-current-value="<?php echo htmlspecialchars($mossa2); ?>"></select>
                        <select name="pokemon[<?php echo $i; ?>][mossa3]" class="mossa-select" data-slot="<?php echo $i; ?>" data-current-value="<?php echo htmlspecialchars($mossa3); ?>"></select>
                        <select name="pokemon[<?php echo $i; ?>][mossa4]" class="mossa-select" data-slot="<?php echo $i; ?>" data-current-value="<?php echo htmlspecialchars($mossa4); ?>"></select>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        <div class="weakness-analysis">
            <h3>Debolezze del Team</h3>
                <table id="tabellaDebolezze">
                    <thead>
                        <tr>
                        <th>Tipo</th>
                        <th>Debolezza</th>
                        </tr>
                    </thead>
                <tbody>
        <?php
        $tipi = [
            "normal", "fire", "water", "electric", "grass", "ice", "fighting", "poison", "ground",
            "flying", "psychic", "bug", "rock", "ghost", "dragon", "dark", "steel", "fairy"
        ];
        foreach ($tipi as $tipo) {
            echo "<tr><td>" . ucfirst($tipo) . "</td><td data-debolezza=\"$tipo\">-</td></tr>";
        }
        ?>
    </tbody>
</table>

        </div>

        </div>

        <div id="suggestions"></div>
        <div class="btn-row">
            <button type="button" class="btn-back" onclick="window.location.href='index.php'">Torna Indietro</button>
            <button type="submit" class="btn-save">Salva Team</button>
        </div>
        
    </form>
</div>

<script>
const pokemonList = <?php echo json_encode($pokemon_list); ?>;
const mosseList = <?php echo json_encode($mosse_list); ?>;
const pokemonMosseData = <?php echo json_encode($pokemon_mosse_data); ?>;
const pokemonAbilitaData = <?php echo json_encode($pokemon_abilita_data); ?>;
const tipiList = <?php echo json_encode($tipi_list); ?>;

const pokemonMap = {};
pokemonList.forEach(p => {
    pokemonMap[p.ID] = p;
});

const typeChart = {
    "normale": {"roccia": 0.5, "acciaio": 0.5, "spettro": 0},
    "fuoco": {"fuoco": 0.5, "acqua": 0.5, "erba": 2, "ghiaccio": 2, "coleottero": 2, "acciaio": 2, "roccia": 0.5},
    "acqua": {"fuoco": 2, "acqua": 0.5, "erba": 0.5, "terra": 2, "roccia": 2},
    "erba": {"fuoco": 0.5, "acqua": 2, "erba": 0.5, "terra": 2, "volante": 0.5, "coleottero": 0.5, "veleno": 0.5, "roccia": 2},
    "elettro": {"acqua": 2, "volante": 2, "erba": 0.5, "elettro": 0.5, "drago": 0.5, "terra": 0},
    "ghiaccio": {"fuoco": 0.5, "ghiaccio": 0.5, "acciaio": 0.5, "lotta": 2, "roccia": 2},
    "lotta": {"normale": 2, "ghiaccio": 2, "roccia": 2, "buio": 2, "acciaio": 2, "volante": 0.5, "psico": 0.5, "folletto": 0.5, "veleno": 0.5, "coleottero": 0.5, "spettro": 0},
    "veleno": {"erba": 2, "folletto": 2, "veleno": 0.5, "terra": 0.5, "roccia": 0.5, "spettro": 0.5, "acciaio": 0},
    "terra": {"fuoco": 2, "elettro": 2, "veleno": 2, "roccia": 2, "acciaio": 2, "erba": 0.5, "coleottero": 0.5, "volante": 0},
    "volante": {"erba": 2, "lotta": 2, "coleottero": 2, "elettro": 0.5, "roccia": 0.5, "acciaio": 0.5},
    "psico": {"lotta": 2, "veleno": 2, "psico": 0.5, "acciaio": 0.5, "buio": 0},
    "coleottero": {"erba": 2, "psico": 2, "buio": 2, "fuoco": 0.5, "lotta": 0.5, "veleno": 0.5, "volante": 0.5, "spettro": 0.5, "acciaio": 0.5, "folletto": 0.5},
    "roccia": {"fuoco": 2, "ghiaccio": 2, "volante": 2, "coleottero": 2, "normale": 0.5, "lotta": 0.5, "veleno": 0.5, "terra": 0.5},
    "spettro": {"psico": 2, "spettro": 2, "buio": 0.5, "normale": 0, "lotta": 0},
    "drago": {"drago": 2, "fuoco": 0.5, "acqua": 0.5, "erba": 0.5, "elettro": 0.5, "folletto": 0},
    "buio": {"psico": 2, "spettro": 2, "lotta": 0.5, "buio": 0.5, "folletto": 0.5},
    "acciaio": {"ghiaccio": 2, "roccia": 2, "folletto": 2, "fuoco": 0.5, "acqua": 0.5, "elettro": 0.5, "acciaio": 0.5},
    "folletto": {"lotta": 2, "drago": 2, "buio": 2, "fuoco": 0.5, "veleno": 0.5, "acciaio": 0.5}
};

function normalizeType(s) {
    return s.toLowerCase().replace(/\s/g, '');
}

function normalizePokemonTypes(types) {
    return types.filter(t => t).map(normalizeType);
}

function calcMultiplier(attacco, difesaTipi) {
    let mult = 1;
    difesaTipi.forEach(tipo => {
        if(typeChart[attacco] && typeChart[attacco][tipo] !== undefined){
            mult *= typeChart[attacco][tipo];
        } else {
            mult *= 1;
        }
    });
    return mult;
}

function updateWeaknessTable() {
    const weaknesses = calculateTeamWeaknesses();
    const tbody = document.getElementById('coverage-body');
    tbody.innerHTML = ''; // Svuota prima di ricostruire
    
    // Ordina i tipi per debolezza (decrescente)
    const sortedWeaknesses = Object.entries(weaknesses)
        .sort((a, b) => b[1] - a[1]);
    
    if (sortedWeaknesses.length === 0 || document.querySelectorAll('.pokemon-select[value!=""]').length === 0) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 2;
        td.textContent = 'Seleziona almeno un Pokémon per vedere le debolezze';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }
    
    sortedWeaknesses.forEach(([tipo, mult]) => {
        const tr = document.createElement('tr');
        
        const tdTipo = document.createElement('td');
        tdTipo.textContent = tipo.toUpperCase();
        tdTipo.classList.add('type-cell');
        
        const tdMult = document.createElement('td');
        // Arrotonda a 2 decimali per chiarezza
        const multRounded = Math.round(mult * 100) / 100;
        tdMult.textContent = multRounded.toFixed(2) + 'x';
        
        // Visualizza con colori diversi in base alla gravità
        if (mult >= 2) {
            tdMult.classList.add('multiplier-high');
            tdMult.style.fontWeight = '800';  // Più marcato per x2 o superiore
        } else if (mult > 1.3) {
            tdMult.classList.add('multiplier-high');
        } else if (mult < 0.5) {
            tdMult.classList.add('multiplier-low');
            tdMult.style.fontWeight = '800';  // Più marcato per x0.5 o inferiore
        } else if (mult < 0.9) {
            tdMult.classList.add('multiplier-low');
        } else {
            tdMult.classList.add('multiplier-normal');
        }
        
        tr.appendChild(tdTipo);
        tr.appendChild(tdMult);
        tbody.appendChild(tr);
    });
}
function updateSprite(slot) {
    const select = document.querySelector(`select.pokemon-select[data-slot='${slot}']`);
    const spriteImg = document.getElementById(`sprite-${slot}`);
    const pokeID = select.value;
    if (pokemonMap[pokeID]) {
        spriteImg.src = pokemonMap[pokeID]['sprite_url'] ?? 'https://via.placeholder.com/64?text=?';
    } else {
        spriteImg.src = 'https://via.placeholder.com/64?text=?';
    }
}

function loadMovesForPokemon(pokemonID, slot) {
    const selects = document.querySelectorAll(`select.mossa-select[data-slot='${slot}']`);
    selects.forEach(sel => {
        const currentValue = sel.getAttribute('data-current-value');
        sel.innerHTML = '<option value="">-- Seleziona Mossa --</option>';
        
        if (!pokemonID || !pokemonMosseData[pokemonID]) return;
        
        const movesForPokemon = pokemonMosseData[pokemonID];
        movesForPokemon.forEach(mossaID => {
            const moveInfo = mosseList.find(m => m.ID == mossaID);
            if (moveInfo) {
                const option = document.createElement('option');
                option.value = moveInfo.ID;
                option.textContent = moveInfo.nome;
                if (moveInfo.ID == currentValue) {
                    option.selected = true;
                }
                sel.appendChild(option);
            }
        });
    });
}

function loadAbilitiesForPokemon(pokemonID, slot) {
    const abilitySelect = document.querySelector(`select.abilita-select[data-slot='${slot}']`);
    const currentAbility = abilitySelect.value;
    abilitySelect.innerHTML = '<option value="">-- Seleziona Abilità --</option>';
    
    if (!pokemonID || !pokemonAbilitaData[pokemonID]) return;
    
    pokemonAbilitaData[pokemonID].forEach(ability => {
        const option = document.createElement('option');
        option.value = ability;
        option.textContent = ability;
        if (ability === currentAbility) {
            option.selected = true;
        }
        abilitySelect.appendChild(option);
    });
}

function init() {
    document.querySelectorAll('.pokemon-select').forEach(select => {
        select.addEventListener('change', e => {
            const slot = e.target.getAttribute('data-slot');
            const pokeID = e.target.value;
            updateSprite(slot);
            loadMovesForPokemon(pokeID, slot);
            loadAbilitiesForPokemon(pokeID, slot);
            updateWeaknessTable();
        });
    });

    for(let i=0; i<6; i++) {
        const select = document.querySelector(`select.pokemon-select[data-slot='${i}']`);
        if(select && select.value) {
            updateSprite(i);
            loadMovesForPokemon(select.value, i);
            loadAbilitiesForPokemon(select.value, i);
        }
    }
    updateWeaknessTable();

document.getElementById("suggerisciBtn").addEventListener("click", () => {
    // Prendiamo tutte le celle della colonna debolezze, supponiamo abbiano classe "debolezzaVal"
    const celle = document.querySelectorAll(".debolezzaVal");
    let maxVal = 0;
    let tipiMax = [];

    celle.forEach(cell => {
        const val = parseFloat(cell.textContent);
        const tipo = cell.dataset.tipo; // supponiamo che la cella abbia un data attribute con il tipo

        if (val > maxVal) {
            maxVal = val;
            tipiMax = [tipo];
        } else if (val === maxVal) {
            tipiMax.push(tipo);
        }
    });

    if (maxVal === 0) {
        alert("Nessuna debolezza da coprire.");
        return;
    }

    // Passiamo i tipi più problematici via URL (ad esempio ?tipi=fuoco,psico)
    const url = "suggerimenti.php?tipi=" + encodeURIComponent(tipiMax.join(","));
    window.open(url, "_blank"); // apre la pagina in nuova scheda
});

}

document.addEventListener('DOMContentLoaded', init);
</script>

<script>

const allTypes = [
    "normal", "fire", "water", "electric", "grass", "ice", "fighting", "poison", "ground",
    "flying", "psychic", "bug", "rock", "ghost", "dragon", "dark", "steel", "fairy"
];

// Funzione per ottenere efficacia di un tipo difensivo da PokéAPI
async function getTypeEffectiveness(typeName) {
    const res = await fetch(`https://pokeapi.co/api/v2/type/${typeName}`);
    const data = await res.json();

    const effectiveness = {};
    allTypes.forEach(t => effectiveness[t] = 1);

    data.damage_relations.double_damage_from.forEach(t => effectiveness[t.name] *= 2);
    data.damage_relations.half_damage_from.forEach(t => effectiveness[t.name] *= 0.5);
    data.damage_relations.no_damage_from.forEach(t => effectiveness[t.name] *= 0);

    return effectiveness;
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("btnSuggest").addEventListener("click", suggestCoverPokemon);
});
</script>

<!-- Variabile JS che deve provenire dal PHP con la lista Pokémon -->
<script>
const pokemonList = <?php echo json_encode(array_map(function($p) {
    return [
        "id" => $p['id'],
        "nome" => strtolower($p['nome']),
        "tipo1" => strtolower($p['tipo1']),
        "tipo2" => isset($p['tipo2']) ? strtolower($p['tipo2']) : null,
        "sprite_url" => $p['sprite_url'] ?? null
    ];
}, $pokemon_list)); ?>;
</script>



</body>
<script>
document.querySelectorAll('select[name^="pokemon"]').forEach(select => {
    select.addEventListener('change', updateTeamDefensiveCoverage);
});
window.addEventListener('load', updateTeamDefensiveCoverage);
</script>
</html>