<?php

namespace Rackspace\CloudFiles\Backup;

use OpenCloud\Rackspace;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Exception\BadResponseException;

class Container
{
    /**
     * @var \OpenCloud\ObjectStore\Resource\Container
     */
    private $store;

    private $max = 30;

    public function __construct(Rackspace $client, $region, $name)
    {
        $this->store = $client->objectStoreService(null, $region)->getContainer($name);
    }

    /**
     * Sube un objeto al contenedor
     *
     * @param  File   $file
     * @return bool
     */
    public function upload(File $file)
    {
        $entityBody = EntityBody::factory($file->content());

        $url = clone $this->store->getUrl();
        $url->addPath($file->path());

        $headers = [];

        $response = $this->store->getClient()->put($url, $headers, $entityBody)->send();

        return $response->getStatusCode() == 201;
    }

    /**
     * Obtiene el contenido de un objeto en el contenedor
     *
     * @param  string $filename
     * @return string
     */
    public function download($filename)
    {
        return $this->get($filename)->getContent();
    }

    /**
     * Obtiene un objeto en el contenedor
     *
     * @param  string $filename
     * @return \OpenCloud\ObjectStore\Resource\DataObject
     */
    public function get($filename = null)
    {
        return $this->store->getObject($filename);
    }

    /**
     * Obtiene la lista de archivos en el container
     */
    public function all()
    {
        return $this->store->objectList();
    }

    public function max($max = null)
    {
        if ($max === null) {
            return $this->max;
        }

        return $this->max = (int) $max;
    }

    public function copy($from, $to)
    {
        return $this->store->dataObject()
            ->setName($from)
            ->copy($this->store->getName() . '/' . $to)
            ->getStatusCode() == 201;
    }

    public function delete($filename)
    {
        try {
            return $this->store->dataObject()
                ->setName($filename)
                ->delete()
                ->getStatusCode() == 204;
        } catch (BadResponseException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                return true;
            }
            throw $e;
        }
    }
}