<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListDatabaseImport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class ListDatabaseImport extends ListImportAbstract
{
    /**
     * Server type flags
     */
    const SERVER_TYPE_MYSQL = 'mysql';
    const SERVER_TYPE_POSTGRESQL = 'pgsql';
    const SERVER_TYPE_SQLSERVER = 'mssql';
    const SERVER_TYPE_ORACLE = 'oci';

    /**
     * @var string
     */
    public $server_type = 'mysql';

    /**
     * @var string
     */
    public $hostname;

    /**
     * @var int
     */
    public $port = 3306;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $database_name;

    /**
     * @var string
     */
    public $table_name;

    /**
     * @var string
     */
    public $email_column = 'email';

    /**
     * @var string
     */
    public $ignored_columns;

    /**
     * @var CDbConnection|null
     */
    protected $_dbConnection;

    /**
     * @var array
     */
    protected $_columns;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['server_type, hostname, port, username, password, database_name, table_name, email_column', 'required'],
            ['database_name, table_name, email_column', 'match', 'pattern' => '/[a-z0-9_]+/i'],
            ['ignored_columns', 'match', 'pattern' => '/[a-z0-9_,\s]+/i'],
            ['port', 'numerical', 'integerOnly' => true],
            ['server_type', 'in', 'range' => array_keys($this->getServerTypes())],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'server_type'       => t('list_import', 'Server type'),
            'hostname'          => t('list_import', 'Hostname'),
            'port'              => t('list_import', 'Port'),
            'username'          => t('list_import', 'Username'),
            'password'          => t('list_import', 'Password'),
            'database_name'     => t('list_import', 'Database name'),
            'table_name'        => t('list_import', 'Table name'),
            'email_column'      => t('list_import', 'Email column'),
            'ignored_columns'   => t('list_import', 'Ignored columns'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'hostname'          => t('list_import', 'i.e: mysql.databaseclusters.com'),
            'port'              => t('list_import', 'i.e: 3306'),
            'username'          => t('list_import', 'i.e: mysqlcluser'),
            'password'          => t('list_import', 'i.e: superprivatepassword'),
            'database_name'     => t('list_import', 'i.e: my_blog'),
            'table_name'        => t('list_import', 'i.e: tbl_subscribers'),
            'email_column'      => t('list_import', 'email'),
            'ignored_columns'   => t('list_import', 'i.e: id, date_added, status'),
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'server_type'       => t('list_import', 'The server type, if not sure choose mysql'),
            'hostname'          => t('list_import', 'The hostname of your database server, it can also be the ip address'),
            'port'              => t('list_import', 'The port where your external database server is listening for connections'),
            'username'          => t('list_import', 'Your username that unique identifies yourself on the database server'),
            'password'          => t('list_import', 'The password for the username'),
            'database_name'     => t('list_import', 'Your database name as you see it in a tool like PhpMyAdmin'),
            'table_name'        => t('list_import', 'Your database table name where your emails are stored, as you see it in a tool like PhpMyAdmin'),
            'email_column'      => t('list_import', 'The column that identified the email address'),
            'ignored_columns'   => t('list_import', 'Which columns should we ignore and not import. Separate multiple columns by a comma'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getServerTypes(): array
    {
        return [
            self::SERVER_TYPE_MYSQL      => t('list_import', 'MySQL'),
            //self::SERVER_TYPE_POSTGRESQL => t('list_import', 'PostgreSQL'),
            self::SERVER_TYPE_SQLSERVER  => t('list_import', 'SQL Server'),
            //self::SERVER_TYPE_ORACLE     => t('list_import', 'Oracle'),
        ];
    }

    /**
     * @return bool
     */
    public function validateAndConnect(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        return $this->getDbConnection() !== null;
    }

    /**
     * @return CDbConnection|null
     */
    public function getDbConnection(): ?CDbConnection
    {
        if ($this->_dbConnection !== null) {
            return $this->_dbConnection;
        }

        try {
            $this->_dbConnection = new CDbConnection($this->getDbConnectionString(), $this->username, $this->password);
            $this->_dbConnection->setActive(true);
            $this->_dbConnection->autoConnect = true;
            $this->_dbConnection->emulatePrepare = true;
            $this->_dbConnection->charset = 'utf8';
            $this->_dbConnection->initSQLs = [
                'SET time_zone="+00:00"',
                'SET NAMES utf8',
                'SET SQL_MODE=""',
             ];
        } catch (Exception $e) {
            $this->addError('hostname', $e->getMessage());
            $this->_dbConnection = null;
        }

        return $this->_dbConnection;
    }

    /**
     * @return string
     */
    public function getDbConnectionString(): string
    {
        $driversMap = [
            self::SERVER_TYPE_MYSQL      => sprintf('mysql:host=%s;port=%d;dbname=%s', $this->hostname, $this->port, $this->database_name),
            //self::SERVER_TYPE_POSTGRESQL => sprintf('pgsql:host=%s;port=%d;dbname=%s', $this->hostname, $this->port, $this->database_name),
            self::SERVER_TYPE_SQLSERVER  => sprintf('sqlsrv:server=%s,%d;database=%s', $this->hostname, $this->port, $this->database_name),
            //self::SERVER_TYPE_ORACLE     => sprintf('oci:dbname=//%s:%d/%s', $this->hostname, $this->port, $this->database_name),
        ];

        return $driversMap[$this->server_type] ?? $driversMap[self::SERVER_TYPE_MYSQL];
    }

    /**
     * @return array
     * @throws CException
     */
    public function getColumns(): array
    {
        if ($this->_columns !== null) {
            return $this->_columns;
        }

        $this->_columns = [];
        $ignore  = (array)explode(',', (string)$this->ignored_columns);
        $ignore  = array_map('trim', $ignore);
        $ignore  = array_map('strtolower', $ignore);

        /** @var CDbConnection $dbConnection */
        $dbConnection = $this->getDbConnection();

        if ($this->server_type == self::SERVER_TYPE_MYSQL) {
            $_columns = $dbConnection->createCommand(sprintf('SHOW COLUMNS FROM `%s`', $this->table_name))->queryAll();
            foreach ($_columns as $data) {
                if (!isset($data['Field'])) {
                    continue;
                }
                if (in_array(strtolower((string)$data['Field']), $ignore)) {
                    continue;
                }
                $this->_columns[] = $data['Field'];
            }
        }

        if ($this->server_type == self::SERVER_TYPE_SQLSERVER) {
            $_columns = $dbConnection->createCommand(sprintf('SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N\'%s\'', $this->table_name))->queryAll();

            foreach ($_columns as $data) {
                if (!isset($data['COLUMN_NAME'])) {
                    continue;
                }
                if (in_array(strtolower($data['COLUMN_NAME']), $ignore)) {
                    continue;
                }

                $this->_columns[] = $data['COLUMN_NAME'];
            }
        }

        return $this->_columns;
    }

    /**
     * @return int
     * @throws CException
     */
    public function countResults(): int
    {
        $count = 0;

        /** @var CDbConnection $dbConnection */
        $dbConnection = $this->getDbConnection();

        if ($this->server_type == self::SERVER_TYPE_MYSQL) {
            $sql = sprintf('SELECT COUNT(*) AS counter FROM `%s` WHERE LENGTH(`%s`) > 0', $this->table_name, $this->email_column);
            $row   = $dbConnection->createCommand($sql)->queryRow();
            $count = $row['counter'];
        }
        if ($this->server_type == self::SERVER_TYPE_SQLSERVER) {
            $sql   = sprintf('SELECT COUNT(*) AS counter FROM [%s] WHERE QUOTENAME(%s) != \'\'', $this->table_name, $this->email_column);
            $row   = $dbConnection->createCommand($sql)->queryRow();
            $count = $row['counter'];
        }
        return (int)$count;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return array
     * @throws CException
     */
    public function getResults(int $offset, int $limit): array
    {
        $results = [];

        /** @var CDbConnection $dbConnection */
        $dbConnection = $this->getDbConnection();

        if ($this->server_type == self::SERVER_TYPE_MYSQL) {
            $columns = '`' . implode('`, `', $this->getColumns()) . '`';
            $columns = preg_replace('/`' . preg_quote($this->email_column, '/') . '`/', '`' . $this->email_column . '` AS email', $columns);
            $sql     = sprintf('SELECT %s FROM `%s` WHERE LENGTH(`%s`) > 0 ORDER BY 1 LIMIT %d OFFSET %d', $columns, $this->table_name, $this->email_column, (int)$limit, (int)$offset);

            $results = $dbConnection->createCommand($sql)->queryAll();
        }

        if ($this->server_type == self::SERVER_TYPE_SQLSERVER) {
            $columns = '[' . implode('], [', $this->getColumns()) . ']';
            $columns = str_replace('[' . $this->email_column . ']', sprintf('[%s] AS email', $this->email_column), $columns);
            $sql     = sprintf('SELECT %s FROM [%s] WHERE QUOTENAME(%s) != \'\' ORDER BY 1 OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', $columns, $this->table_name, $this->email_column, (int)$offset, (int)$limit);
            $results = $dbConnection->createCommand($sql)->queryAll();
        }
        return $results;
    }
}
