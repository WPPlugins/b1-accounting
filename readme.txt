=== B1.lt ===
Contributors: b1accounting
Requires at least: 2.6.8
Tested up to: 4.7.5
Stable tag: trunk

== Description ==
Įskiepis skirtas sinchronizuoti produktus tarp WooCommerce ir B1.lt aplikacijos.

== Installation ==
### Reikalavimai ###

* PHP 5.5
* WooCommerce 2.6.8
* MySQL 5.7.15

### Diegimas ###

* `NEBŪTINA` Pasidarykite failų atsarginę kopiją.
* Padarykite atsarginę DB kopiją.
* Perkelkite `b1accounting` direktoriją į woocommerce `wp-content/plugins/` direktoriją.
* Administracijos skiltyje įdiekite modulį ir suveskite reikiamą informaciją.
* Administracijos skiltyje 'Orders sync from' laukelyje nurodykite data, nuo kurios bus sinchromnizuojami užsakymai. Datos formatas Y-m-d. Pvz. 2016-12-10 
* Administtracijos skiltyje 'VAT Tax rate ID', nurodykite (jei taikomas PVM) parduotuvės mokėsčio ID. Jos rasite žemiau laukelio.
* Administracijos skiltyje 'Items relations for link' nurodykite prekių susiejimo būdą:
    * One to one - surišama vieną parduotuvės prekė su viena B1 preke, kiekiai yra sinchronizuojami
    * More to one - surišamos keletas parduotuvės prekių su viena B1 preke, kiekiai nėra sinchronizuojami
* Prie serverio Cron darbų sąrašo pridėkite visus išvardintus cron darbus, nurodytus modulio konfigūravimo puslapyje.
  Pridėti cron darbus galite per serverio valdymo panelė (DirectAdmin, Cpanel) arba įvykdę šias komandinės eilutės serverio pusėje
    * `0 */12 * * * wget -q -O - '[products_cron_url]'` Vietoj [products_cron_url] reikia nurodyti savo Cron adresą. 
    * `*/5 * * * * wget -q -O - '[orders_cron_url]'` Vietoj [orders_cron_url] reikia nurodyti savo Cron adresą. 
    * `0 */4 * * *  wget -q -O - '[quantities_cron_url]'` Vietoj [quantities_cron_url] reikia nurodyti savo Cron adresą. 
* Susiekite B1 ir e.parduotuvės prekės `Nesusiję produktai` skiltyje.
* Įvykdykite cron prekių kiekio sinchronizacijai.
* Norėdami, kad pirkėjai matytų B1 sugeneruotas sąskaitas, reikia `wp-content\plugins\woocommerce\templates\order\order-details.php` faile įterpti norimoje puslapio vietoje nuorodą pvz. 

`&lt;?php`
`include_once( ABSPATH . 'wp-admin/includes/plugin.php' );`
`if ( is_plugin_active( 'b1accounting/b1accounting.php' ) ) {`
`?&gt;`
&lt;a target="_new" href='<`?php echo get_query_var( 'b1_accounting_link' ) ?`>'&gt;PDF&lt;/a&gt;
`&lt;?php } ?&gt;`


### Pastabos ####

Į B1 siunčiami TIK užsakymai su statusu "Completed" (reikšmė 'wc-completed').
Užsakymo data yra laikoma, tą kuri yra nurodyta prie užsakymo e.parduotuvėje. Norint, kad data sutaptų su mokėjimų, prieš patvirtinant užsakymą reikia pakeisti ir šią datą. 

### Kontaktai ###

* Kilus klausimams, prašome kreiptis info@b1.lt

== Changelog ==
No changes

== Upgrade Notice ==
No notes

== Screenshots ==
1. **Settings** - Settings configuration page
2. **Unlinked products** - B1.lt and E.shop unlinked products list
3. **Linked products** - B1.lt and E.shop linked products list