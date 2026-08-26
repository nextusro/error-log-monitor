<?php

declare(strict_types=1);

$english = require __DIR__.'/../en/messages.php';

return array_replace_recursive($english, [
    'dashboard' => ['description' => 'Dashboard pentru erori, warning-uri și mesaje critice din logurile aplicației.', 'settings' => 'Setări', 'open_settings' => 'Deschide setările', 'change_theme' => 'Schimbă tema'],
    'settings' => [
        'title' => 'Setări Error Log Monitor', 'description' => 'Configurează comportamentul monitorului.', 'close' => 'Închide', 'groups' => 'Grupuri de setări',
        'general' => 'General', 'indexing' => 'Indexare', 'notifications' => 'Notificări', 'retention' => 'Retenție',
        'language' => 'Limbă', 'language_description' => 'Alege limba folosită în dashboardul Error Log Monitor.', 'save_language' => 'Salvează limba',
        'language_saved' => 'Limba dashboardului a fost actualizată.', 'monitoring' => 'Monitorizare loguri',
        'monitoring_description' => 'Controlează indexarea erorilor noi. Dashboardul și erorile existente rămân disponibile.',
        'active' => 'Activă', 'suspended' => 'Suspendată', 'monitoring_config_disabled' => 'Monitorizarea este dezactivată prin ERROR_LOG_MONITOR_ENABLED și nu poate fi activată din dashboard.',
        'suspend_warning' => 'După suspendare nu se vor mai adăuga erori în monitor până la reactivare.', 'suspend' => 'Suspendă monitorizarea',
        'resume_question' => 'Cum dorești să reiei monitorizarea?', 'catch_up' => 'Recuperează erorile disponibile',
        'catch_up_description' => 'Continuă de la ultimul cursor. Unele erori pot lipsi dacă logurile au fost rotite, comprimate sau șterse.',
        'from_now' => 'Monitorizează doar erorile viitoare', 'from_now_description' => 'Ignoră conținutul actual și începe de la finalul fișierelor existente.',
        'enable' => 'Activează monitorizarea', 'bulk_actions' => 'Acțiuni bulk',
        'bulk_actions_description' => 'Permite marcarea simultană ca rezolvate sau ignorate a issue-urilor deschise.', 'disabled' => 'Dezactivate',
        'enable_bulk' => 'Activează acțiunile bulk', 'disable_bulk' => 'Dezactivează acțiunile bulk',
    ],
    'monitoring' => ['paused_title' => 'Monitorizarea este suspendată.', 'paused_description' => 'Erorile continuă să fie scrise în logurile aplicației, dar nu sunt indexate în dashboard.', 'reactivate' => 'Reactivează', 'disabled_by_config' => 'Dezactivată din configurația aplicației', 'suspended' => 'Monitorizarea a fost suspendată. Erorile noi nu vor fi indexate.', 'enabled_from_now' => 'Monitorizarea a fost activată. :count fișiere vor fi urmărite doar pentru erorile viitoare.', 'enabled_catch_up' => 'Monitorizarea a fost activată și va recupera erorile încă disponibile în fișierele de log.'],
    'bulk' => ['enabled' => 'Acțiunile bulk au fost activate.', 'disabled' => 'Acțiunile bulk au fost dezactivate.', 'ignored' => ':count issue-uri au fost ignorate.', 'resolved' => ':count issue-uri au fost marcate ca rezolvate.', 'ignore_selected' => 'Ignoră selectate', 'resolve_selected' => 'Rezolvă selectate', 'select_all' => 'Selectează toate issue-urile deschise de pe pagină', 'select_issue' => 'Selectează issue-ul :id'],
    'filters' => ['all' => 'Toate', 'query_placeholder' => 'Caută în mesaj, excepție, stack trace...', 'file' => 'Fișier', 'all_files' => 'Toate fișierele', 'directory' => 'Subdirector', 'all_directories' => 'Toate subdirectoarele', 'filter' => 'Filtrează', 'reset' => 'Resetează'],
    'status' => ['open' => 'Deschise', 'resolved' => 'Rezolvate', 'ignored' => 'Ignorate', 'all' => 'Toate'],
    'intervals' => ['1h' => 'Ultima oră', '24h' => 'Ultimele 24 de ore', '7d' => 'Ultimele 7 zile', '14d' => 'Ultimele 14 zile', 'all' => 'Tot intervalul'],
    'statistics' => ['description' => 'Calculat doar pentru intervalul selectat: :interval. Warning-urile nu sunt incluse.', 'expand' => 'Extinde', 'collapse' => 'Restrânge', 'open_issues' => 'Erori deschise', 'active_in_interval' => 'active în interval', 'new_issues' => 'Erori noi', 'first_seen_in_interval' => 'prima apariție în interval', 'occurrences' => 'Apariții', 'occurrences_in_interval' => 'apariții în interval', 'critical_open' => 'Critice deschise', 'regressions' => 'Regresii', 'regressions_hint' => 'rezolvate, dar reapărute', 'last_indexed' => 'Ultima indexare', 'last_scan' => 'ultima scanare', 'db_records' => 'Înregistrări DB', 'db_size' => 'Dimensiune DB', 'db_size_hint' => 'date + indexuri pentru tabelele monitorului', 'top_issues' => 'Erori recurente', 'top_sources' => 'Surse principale', 'no_data' => 'Nu există date în intervalul selectat.', 'view_issue' => 'Vezi eroarea în listă'],
    'issues' => ['title' => 'Erori', 'empty' => 'Nu există erori pentru filtrele selectate.', 'showing' => 'Afișare :first-:last din :total issue-uri.', 'message' => 'Mesaj', 'occurrences' => 'Apariții', 'first_seen' => 'Prima apariție', 'last_seen' => 'Ultima apariție', 'last_source' => 'Ultima sursă', 'actions' => 'Acțiuni', 'regression' => 'Regresie', 'resolve' => 'Marchează ca rezolvat', 'ignore' => 'Ignoră', 'reopen' => 'Redeschide', 'context' => 'Context:', 'stack_trace' => 'Stack trace:', 'previous_page' => 'Pagina anterioară', 'next_page' => 'Pagina următoare', 'updated' => 'Eroarea a fost actualizată.'],
    'validation' => ['locale_required' => 'Selectează o limbă.', 'locale_invalid' => 'Limba selectată nu este disponibilă.', 'bulk_required' => 'Selectează cel puțin o eroare.', 'bulk_max' => 'Poți modifica maximum 500 de erori într-o singură operație.', 'bulk_exists' => 'Una dintre erorile selectate nu mai există.', 'bulk_state_required' => 'Starea acțiunilor bulk este obligatorie.', 'monitoring_state_required' => 'Starea monitorizării este obligatorie.', 'resume_mode_required' => 'Selectează cum dorești să reiei monitorizarea.'],
    'javascript' => ['confirm_suspend' => 'Confirmi suspendarea monitorizării? Erorile noi nu vor mai fi indexate până la reactivare.', 'confirm_bulk' => 'Confirmi marcarea issue-urilor selectate ca :action?', 'changed' => 'modificate'],
]);
