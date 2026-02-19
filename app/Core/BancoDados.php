<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class BancoDados
{
    private static ?PDO $conexao = null;

    public static function conectar(array $configuracao): PDO
    {
        if (self::$conexao !== null) {
            return self::$conexao;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $configuracao['host'],
            $configuracao['porta'],
            $configuracao['nome'],
            $configuracao['charset']
        );

        try {
            self::$conexao = new PDO($dsn, $configuracao['usuario'], $configuracao['senha'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $excecao) {
            die('Erro ao conectar no banco de dados: ' . $excecao->getMessage());
        }

        return self::$conexao;
    }
}
