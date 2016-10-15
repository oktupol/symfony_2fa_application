<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-15
 * Time: 15:51
 */

namespace AppBundle\Command;


use AppBundle\Entity\Auth\Role;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    protected function configure()
    {
        $this->setName('app:initialize')
            ->setDescription('Initialize this application after installation');
    }

    private function init()
    {
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init();

        $this->createRoles();
    }

    private function createRoles()
    {
        $roles = array(
            'ROLE_ADMIN',
            'ROLE_USER'
        );

        foreach ($roles as $role) {
            $existingRoleObject = $this->entityManager->getRepository('AppBundle:Auth\Role')->findOneBy(array(
                'name' => $role
            ));

            if ($existingRoleObject !== null) {
                continue;
            }

            $newRoleObject = (new Role())->setName($role);
            $this->entityManager->persist($newRoleObject);
        }

        $this->entityManager->flush();
    }
}