<?php
/**
 * Il file base di configurazione di WordPress.
 *
 * Questo file viene utilizzato, durante l’installazione, dallo script
 * di creazione di wp-config.php. Non è necessario utilizzarlo solo via web
 * puoi copiare questo file in «wp-config.php» e riempire i valori corretti.
 *
 * Questo file definisce le seguenti configurazioni:
 *
 * * Impostazioni database
 * * Chiavi Segrete
 * * Prefisso Tabella
 * * ABSPATH
 *
 * * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Impostazioni database - È possibile ottenere queste informazioni dal proprio fornitore di hosting ** //
/** Il nome del database di WordPress */
define( 'DB_NAME', 'jhbkzlzj_woocommerce' );


/** Nome utente del database */
define( 'DB_USER', 'jhbkzlzj_woocommerce' );


/** Password del database */
define( 'DB_PASSWORD', 'w00c0mm3rc3!_' );


/** Hostname del database */
define( 'DB_HOST', 'localhost' );


/** Charset del Database da utilizzare nella creazione delle tabelle. */
define( 'DB_CHARSET', 'utf8mb4' );


/** Il tipo di Collazione del Database. Da non modificare se non si ha idea di cosa sia. */
define('DB_COLLATE', '');

/**#@+
 * Chiavi Univoche di Autenticazione e di Salatura.
 *
 * Modificarle con frasi univoche differenti!
 * È possibile generare tali chiavi utilizzando {@link https://api.wordpress.org/secret-key/1.1/salt/ servizio di chiavi-segrete di WordPress.org}
 * È possibile cambiare queste chiavi in qualsiasi momento, per invalidare tuttii cookie esistenti. Ciò forzerà tutti gli utenti ad effettuare nuovamente il login.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'N8~ 96xe|ywcNcum7~{)R~P#K6/lKlddgXqf!+~mfgR?r6GOk]8gE&x]A)7GjB/x' );

define( 'SECURE_AUTH_KEY',  'aP;DH]HhW(zZlRgHf!e/t*>1}c-Q[|=q#|J`D0zghi.$O-*AL?4M./oj`wEnx3M-' );

define( 'LOGGED_IN_KEY',    'S2ZItT}t<[Z,jMr!x18z-3F-2CgYQ^LxPkr8s!+AE}C@JbN1T}E:7X7R-@S47TyL' );

define( 'NONCE_KEY',        'OXTR](&T`M]q-!i3bDhU[3G167?kq>omXH`5*qP`q0u*A-M]ft[3,V=o}HqiEuhr' );

define( 'AUTH_SALT',        'xZ3)&4_T-}m?r.fF2$P-QEtwQ/OxFBym&$8O]oXZ7(I(#/T8,+0=OJJ:|i#zm@=I' );

define( 'SECURE_AUTH_SALT', '@B_Po`D#SJCvIVRU}7GnDG@sXhAZ^0(^/)x-(Q Qp89*dE6DoF$]!4@szU&1F5W:' );

define( 'LOGGED_IN_SALT',   '/UFSf`++Dh- [s9Br#|C ^(OH$3])o]&xa|Kzr`NeSfxte3b7puZb=6 3x7@eFwq' );

define( 'NONCE_SALT',       '%_Gn&o~*nv#}||l^r2.S+T*4w3dx-w!n871f7_(=;6AUo-f|E[Q*8yz|&CCsU&gg' );


/**#@-*/

/**
 * Prefisso Tabella del Database WordPress.
 *
 * È possibile avere installazioni multiple su di un unico database
 * fornendo a ciascuna installazione un prefisso univoco.
 * Solo numeri, lettere e sottolineatura!
 */
$table_prefix = 'wp_';


/**
 * Per gli sviluppatori: modalità di debug di WordPress.
 *
 * Modificare questa voce a TRUE per abilitare la visualizzazione degli avvisi durante lo sviluppo
 * È fortemente raccomandato agli svilupaptori di temi e plugin di utilizare
 * WP_DEBUG all’interno dei loro ambienti di sviluppo.
 *
 * Per informazioni sulle altre costanti che possono essere utilizzate per il debug,
 * leggi la documentazione
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define('WP_DEBUG', true);

/* Finito, interrompere le modifiche! Buon blogging. */

/** Path assoluto alla directory di WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Imposta le variabili di WordPress ed include i file. */
require_once(ABSPATH . 'wp-settings.php');
