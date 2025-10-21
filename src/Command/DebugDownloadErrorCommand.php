<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-download-error',
    description: 'Aide au débogage de l\'erreur de téléchargement',
)]
class DebugDownloadErrorCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔍 Guide de débogage - Erreur de téléchargement');

        $io->section('📋 Checklist de vérification');

        $checklist = [
            '✅ Vérifiez que vous êtes connecté à l\'interface web',
            '✅ Vérifiez que votre session n\'a pas expiré',
            '✅ Essayez de vous déconnecter et reconnecter',
            '✅ Videz le cache de votre navigateur',
            '✅ Vérifiez les logs d\'erreur du serveur',
            '✅ Testez avec un autre navigateur',
            '✅ Vérifiez que JavaScript est activé'
        ];

        foreach ($checklist as $item) {
            $io->writeln($item);
        }

        $io->section('🔧 Actions de dépannage');

        $actions = [
            '1. Vérifiez les logs Symfony:',
            '   tail -f var/log/dev.log',
            '',
            '2. Vérifiez les logs du serveur web:',
            '   tail -f /var/log/apache2/error.log',
            '   ou',
            '   tail -f /var/log/nginx/error.log',
            '',
            '3. Testez le téléchargement en ligne de commande:',
            '   php bin/console app:test-document-download --document-id=42 --user-id=18',
            '',
            '4. Vérifiez la configuration de session:',
            '   php -i | grep session',
            '',
            '5. Redémarrez le serveur web:',
            '   sudo systemctl restart apache2',
            '   ou',
            '   sudo systemctl restart nginx'
        ];

        foreach ($actions as $action) {
            $io->writeln($action);
        }

        $io->section('🚨 Causes courantes');

        $causes = [
            '❌ Utilisateur non authentifié',
            '❌ Session expirée',
            '❌ Problème de cookies',
            '❌ Cache du navigateur',
            '❌ JavaScript désactivé',
            '❌ Problème de réseau',
            '❌ Erreur de configuration serveur',
            '❌ Permissions insuffisantes'
        ];

        foreach ($causes as $cause) {
            $io->writeln($cause);
        }

        $io->section('💡 Solutions rapides');

        $solutions = [
            '🔄 Reconnectez-vous à l\'interface web',
            '🧹 Videz le cache de votre navigateur',
            '🔧 Redémarrez votre navigateur',
            '📱 Testez avec un autre navigateur',
            '🌐 Testez en navigation privée',
            '⚙️  Vérifiez les paramètres de cookies',
            '🔒 Vérifiez que HTTPS fonctionne (si applicable)'
        ];

        foreach ($solutions as $solution) {
            $io->writeln($solution);
        }

        $io->section('📞 Support technique');

        $support = [
            'Si le problème persiste:',
            '1. Collectez les logs d\'erreur',
            '2. Notez les étapes pour reproduire l\'erreur',
            '3. Indiquez le navigateur et la version utilisée',
            '4. Précisez si l\'erreur survient sur tous les documents ou un seul'
        ];

        foreach ($support as $item) {
            $io->writeln($item);
        }

        $io->success('Guide de débogage terminé. Suivez les étapes ci-dessus pour résoudre le problème.');

        return Command::SUCCESS;
    }
}
