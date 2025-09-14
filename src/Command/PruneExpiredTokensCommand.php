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
    description: 'Remove all expired access tokens'
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

        $io->info('Removing expired access tokens...');

        $deletedCount = $this->tokenService->purgeExpiredTokens();

        if ($deletedCount > 0) {
            $io->success(sprintf('Removed %d expired access tokens.', $deletedCount));
        } else {
            $io->info('No expired access tokens found.');
        }

        return Command::SUCCESS;
    }
}
