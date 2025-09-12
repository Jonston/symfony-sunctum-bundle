<?php

namespace Jonston\SanctumBundle\Command;

use Jonston\SanctumBundle\Service\TokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'sanctum:prune-expired',
    description: 'Удалить все просроченные токены доступа'
)]
class PruneExpiredTokensCommand extends Command
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Удаление просроченных токенов...');

        $deletedCount = $this->tokenService->purgeExpiredTokens();

        if ($deletedCount > 0) {
            $io->success(sprintf('Удалено %d просроченных токенов.', $deletedCount));
        } else {
            $io->info('Просроченные токены не найдены.');
        }

        return Command::SUCCESS;
    }
}
