<?php

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION AND SESSION STORAGE (shared by both apps)
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/config.php";


/*
 * Opens a connection to the `aquado` database. Throws mysqli_sql_exception
 * when the settings are wrong.
 */
function aquadoConnect() {

    $conn = mysqli_init();

    if (DB_SSL) {
        // Hosted MySQL services (such as PlanetScale) only accept encrypted connections.
        $conn->ssl_set(null, null, null, null, null);
        $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, null, null, MYSQLI_CLIENT_SSL);
    } else {
        $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }

    $conn->set_charset("utf8mb4");

    return $conn;

}


/*
 * Keeps PHP sessions in the `sessions` table instead of the server's temp
 * folder. When requests can land on different servers (Vercel), file
 * sessions would log people out at random.
 */
class AquadoSessionHandler implements SessionHandlerInterface {

    private $conn = null;

    private function connection() {

        if ($this->conn === null) {
            $this->conn = aquadoConnect();
        }

        return $this->conn;

    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {

        $stmt = $this->connection()->prepare("SELECT data FROM sessions WHERE id = ? AND last_access > ?");
        $oldest = time() - (int)ini_get("session.gc_maxlifetime");
        $stmt->bind_param("si", $id, $oldest);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ? $row["data"] : "";

    }

    public function write(string $id, string $data): bool {

        $stmt = $this->connection()->prepare("
            INSERT INTO sessions (id, data, last_access)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                data = VALUES(data),
                last_access = VALUES(last_access)
        ");
        $now = time();
        $stmt->bind_param("ssi", $id, $data, $now);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;

    }

    public function destroy(string $id): bool {

        $stmt = $this->connection()->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;

    }

    public function gc(int $max_lifetime): int|false {

        $stmt = $this->connection()->prepare("DELETE FROM sessions WHERE last_access < ?");
        $oldest = time() - $max_lifetime;
        $stmt->bind_param("i", $oldest);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        return $deleted;

    }

}
