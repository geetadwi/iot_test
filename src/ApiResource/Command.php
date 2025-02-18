<?php

namespace App\ApiResource;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\CommandProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[Post(processor: CommandProcessor::class)]
#[ApiResource(
    operations: [
        new Get(
            controller: NotFoundAction::class,
            output: false,
            read: false
        ),
        new Post(),
        new GetCollection(
            openapiContext: [
                'summary' => 'Simulate the modules',
                'responses' => [
                    '200' => [
                        'description' => 'Simulation of modules successfully.',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'date' => ['type' => 'string', 'format' => 'date-time'],
                                            'dateAgo' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ),
    ],
    normalizationContext: ['groups' => ['command:read']],
    denormalizationContext: ['groups' => ['command:write']],
    paginationEnabled: false,
)]
#[ORM\HasLifecycleCallbacks]
class Command
{
    const READ = "command:read";
    const MERCURE_TOPIC = "/api/commands";

    #[Groups(["command:read"])]
    public ?\DateTimeInterface $date = null;


    public function __construct(?\DateTimeInterface $date = null)
    {
        $this->date = $date ?? new \DateTime();
    }

}
