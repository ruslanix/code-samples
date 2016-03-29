<?php
namespace Mesh\RestBundle\Listener;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;

use Mesh\RestBundle\Services\MediaThumbnailManager;

class MediaSerializerListener implements EventSubscriberInterface
{
    /**
     *
     * @var \Mesh\RestBundle\Services\MediaThumbnailManager
     */
    private $thumbnailManager;

    public function setThumbnailManager(MediaThumbnailManager $thumbnailManager)
    {
        $this->thumbnailManager = $thumbnailManager;
    }

    /**
     * @inheritdoc
     */
    static public function getSubscribedEvents()
    {
        return array(
            array('event' => 'serializer.post_serialize', 'class' => 'Mesh\EncompassBundle\Entity\Media', 'method' => 'onPostSerializeMedia'),
        );
    }

    public function onPostSerializeMedia(ObjectEvent $event)
    {
        $entity = $event->getObject();

        if (!$entity->isImage()) {
            return;
        }

        if ($this->isSerializationGroupEnabled($event, 'details')) {
            $event->getVisitor()->addData('thumbs', $this->thumbnailManager->getThumbnailsUrls($entity));
        }

        $event->getVisitor()->addData('thumb', $this->thumbnailManager->getMainThumbnailUrl($entity));
    }

    protected function isSerializationGroupEnabled(ObjectEvent $event, $groupName)
    {
        return in_array($groupName, $event->getContext()->attributes->get('groups')->get());
    }
}