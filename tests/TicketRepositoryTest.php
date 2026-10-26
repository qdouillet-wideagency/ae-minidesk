<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de TicketRepository sur une base SQLite en mémoire
 * (la base MySQL réelle n'est jamais utilisée).
 */
final class TicketRepositoryTest extends TestCase
{
    private PDO $pdo;
    private TicketRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec(
            "CREATE TABLE tickets (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                title       VARCHAR(255) NOT NULL,
                description TEXT,
                priority    TEXT NOT NULL DEFAULT 'medium',
                status      TEXT NOT NULL DEFAULT 'open',
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
        $this->repo = new TicketRepository($this->pdo);
    }

    public function testFindAllReturnsEmptyArrayWhenNoTickets(): void
    {
        $this->assertSame([], $this->repo->findAll());
    }

    public function testFindAllOrdersByCreatedAtDesc(): void
    {
        $this->pdo->exec("INSERT INTO tickets (title, created_at) VALUES ('old', '2024-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO tickets (title, created_at) VALUES ('new', '2026-01-01 00:00:00')");

        $titles = array_column($this->repo->findAll(), 'title');

        $this->assertSame(['new', 'old'], $titles);
    }

    public function testCreateReturnsIdAndPersistsData(): void
    {
        $id = $this->repo->create(['title' => 'T', 'description' => 'D', 'priority' => 'high']);

        $ticket = $this->repo->findById($id);
        $this->assertSame('T', $ticket['title']);
        $this->assertSame('D', $ticket['description']);
        $this->assertSame('high', $ticket['priority']);
        $this->assertSame('open', $ticket['status']);
    }

    public function testCreateAppliesDefaults(): void
    {
        $id = $this->repo->create(['title' => 'Only title']);

        $ticket = $this->repo->findById($id);
        $this->assertSame('', $ticket['description']);
        $this->assertSame('medium', $ticket['priority']);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repo->findById(999));
    }

    public function testFindByIdIsNotVulnerableToSqlInjection(): void
    {
        $this->repo->create(['title' => 'A']);
        $this->repo->create(['title' => 'B']);

        // Le typage int empêche une chaîne malveillante d'atteindre la requête.
        $this->expectException(TypeError::class);
        $this->repo->findById('1 OR 1=1');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sqlInjectionPayloads(): array
    {
        return [
            'tautologie'      => ['1 OR 1=1'],
            'union'           => ['1 UNION SELECT * FROM tickets'],
            'requêtes liées'  => ['1; DROP TABLE tickets;--'],
            'commentaire'     => ['1--'],
            'quote'           => ["1' OR '1'='1"],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sqlInjectionPayloads')]
    public function testFindByIdRejectsInjectionPayloadsAndKeepsData(string $payload): void
    {
        $this->repo->create(['title' => 'A']);
        $this->repo->create(['title' => 'B']);

        try {
            $this->repo->findById($payload);
            $this->fail('Un paramètre non entier doit être refusé.');
        } catch (TypeError) {
            // attendu : le typage int bloque le payload avant la requête
        }

        $this->assertCount(2, $this->repo->findAll());
    }

    public function testFindByIdReturnsOnlyTheRequestedRow(): void
    {
        $a = $this->repo->create(['title' => 'A']);
        $this->repo->create(['title' => 'B']);

        $this->assertSame('A', $this->repo->findById($a)['title']);
    }

    public function testFindByIdReturnsNullForNegativeOrZeroId(): void
    {
        $this->repo->create(['title' => 'A']);

        $this->assertNull($this->repo->findById(0));
        $this->assertNull($this->repo->findById(-1));
    }

    public function testUpdateStatusWithValidStatus(): void
    {
        $id = $this->repo->create(['title' => 'A']);

        foreach (['in_progress', 'closed', 'open'] as $status) {
            $this->assertTrue($this->repo->updateStatus($id, $status));
            $this->assertSame($status, $this->repo->findById($id)['status']);
        }
    }

    public function testUpdateStatusRejectsInvalidStatus(): void
    {
        $id = $this->repo->create(['title' => 'A']);

        $this->assertFalse($this->repo->updateStatus($id, 'done'));
        $this->assertFalse($this->repo->updateStatus($id, ''));
        $this->assertFalse($this->repo->updateStatus($id, "closed'; DROP TABLE tickets;--"));
        $this->assertSame('open', $this->repo->findById($id)['status']);
    }

    public function testUpdateStatusReturnsFalseWhenTicketMissing(): void
    {
        $this->assertFalse($this->repo->updateStatus(999, 'closed'));
    }

    public function testUpdateStatusOnlyAffectsTargetTicket(): void
    {
        $a = $this->repo->create(['title' => 'A']);
        $b = $this->repo->create(['title' => 'B']);

        $this->repo->updateStatus($a, 'closed');

        $this->assertSame('open', $this->repo->findById($b)['status']);
    }
}
