<?php
// LDAP-Konfiguration
$ldapserver = "ldaps://dc.domain.local";
$ldaprdn    = "domain\user";
$ldappass   = "PASSWORD";
$basedn     = "OU=Users,DC=domain,DC=local";
$filter     = "(&(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2))(telephoneNumber=*))";
$attributes = ["displayName", "telephoneNumber", "mail", "department", "title"];

// Verbindung
$ldapconn = ldap_connect($ldapserver);
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

$results = [];
$departments = [];

if ($ldapconn && ldap_bind($ldapconn, $ldaprdn, $ldappass)) {
    $search = ldap_search($ldapconn, $basedn, $filter, $attributes);
    $entries = ldap_get_entries($ldapconn, $search);

    for ($i = 0; $i < $entries["count"]; $i++) {
        $entry = $entries[$i];
        $dep = $entry["department"][0] ?? "Unbekannt";
        $departments[] = $dep;
        $results[] = [
            "name" => $entry["displayname"][0] ?? '',
            "tel" => $entry["telephonenumber"][0] ?? '',
            "mail" => $entry["mail"][0] ?? '',
            "title" => $entry["title"][0] ?? '',
            "department" => $dep
        ];
    }

    ldap_unbind($ldapconn);
}

// Statische Einträge, die nicht im LDAP vorhanden sind
$results[] = [
    "name" => "Max Mustermann",
    "tel" => "111",
    "mail" => "",
    "title" => "",
    "department" => "Abteilung 1"
];
$departments[] = "Abteilung 1";

// Abteilungen alphabetisch + eindeutig
$departments = array_unique($departments);
sort($departments);

usort($results, function($a, $b) {
    $lastNameA = explode(' ', $a['name']);
    $lastNameB = explode(' ', $b['name']);
    return strcmp($lastNameA[1] ?? $lastNameA[0], $lastNameB[1] ?? $lastNameB[0]);
});

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Telefonliste</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .filters { margin-bottom: 20px; }
        .filter-button {
            margin: 5px;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .filter-button.active { background-color: #2e7d32; }
        table { border-collapse: collapse; width: 80%; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f9f9f9; }
    </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
    <h1 style="margin: 0;">Telefonliste</h1>
    <div style="position: relative;">
        <input type="text" id="suche" placeholder="Suchen..."
               style="padding: 8px 32px 8px 12px; font-size: 16px; border: 1px solid #ccc; border-radius: 4px;">
        <i class="fas fa-search" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #888;"></i>
    </div>
</div>

    <div class="filters">
        <button class="filter-button active" data-dep="all">Alle Abteilungen</button>
        <?php foreach ($departments as $dep): ?>
            <button class="filter-button" data-dep="<?= htmlspecialchars($dep) ?>"><?= htmlspecialchars($dep) ?></button>
        <?php endforeach; ?>
    </div>

    <table id="telefonTabelle">
        <thead>
            <tr>
                <th>Name</th>
                <th>Telefon</th>
                <th>Abteilung</th>
                <th>Position</th>
                <th>E-Mail</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $person): ?>
            <tr data-department="<?= htmlspecialchars($person['department']) ?>">
                <td><?= htmlspecialchars($person['name']) ?></td>
                <td><?= htmlspecialchars($person['tel']) ?></td>
                <td><?= htmlspecialchars($person['department']) ?></td>
                <td><?= htmlspecialchars($person['title']) ?></td>
                <td><?= $person['mail'] ? '<a href="mailto:' . htmlspecialchars($person['mail']) . '">' . htmlspecialchars($person['mail']) . '</a>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        const buttons = document.querySelectorAll('.filter-button');
        const rows = document.querySelectorAll('#telefonTabelle tbody tr');

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                buttons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                const dep = button.dataset.dep;

                rows.forEach(row => {
                    const rowDep = row.dataset.department;
                    if (dep === 'all' || rowDep === dep) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>

        <script>
    document.getElementById('suche').addEventListener('input', function () {
        const suchbegriff = this.value.toLowerCase();
        const zeilen = document.querySelectorAll('#telefonTabelle tbody tr');

        zeilen.forEach(zeile => {
            const text = zeile.textContent.toLowerCase();
            zeile.style.display = text.includes(suchbegriff) ? '' : 'none';
        });
    });
</script>
</body>
</html>
