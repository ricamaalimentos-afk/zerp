<?php

declare(strict_types=1);

const APP_NOME = 'ZERP - Sistema ERP';
const APP_VERSAO = '0.1.0';
const APP_URL = 'http://localhost/zerp/public';
const APP_FUSO = 'America/Sao_Paulo';
const APP_MOEDA = 'BRL';
const APP_LOCALE = 'pt_BR';

date_default_timezone_set(APP_FUSO);
setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
