<?php


/**
 *
 * Copyright (c) 2010-2017 Nevraxe inc. & Marc André Audet <maudet@nevraxe.com>. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 *   1. Redistributions of source code must retain the above copyright notice, this list of
 *       conditions and the following disclaimer.
 *
 *   2. Redistributions in binary form must reproduce the above copyright notice, this list
 *       of conditions and the following disclaimer in the documentation and/or other materials
 *       provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */


namespace Cervo;


use Cervo\Core as _;
use Cervo\Exceptions\TemplateFileMissingException;


/**
 * Template class for Cervo.
 *
 * @author Marc André Audet <maudet@nevraxe.com>
 */
final class Template
{
    /**
     * The template path.
     * @var array
     */
    private $path;

    /**
     * The template's data.
     * Usually set from the View.
     * @var array
     */
    private $data = [];

    /**
     * Initialize the template.
     *
     * @param string $name The template (file)name.
     *
     * @throws TemplateFileMissingException if the template's file doesn't exists.
     */
    public function __construct($name)
    {
        $config = _::getLibrary('Cervo/Config');

        $ex_name = explode('/', $name);

        $this->path = $config->get('Cervo/Application/Directory') . $ex_name[0] . \DS . $config->get('Cervo/Application/TemplatesPath') . implode('/', array_slice($ex_name, 1)) . '.php';

        if (!file_exists($this->path)) {
            throw new TemplateFileMissingException();
        }
    }

    /**
     * Magic method to return the data.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Not so magic method to return the data.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } else {
            return null;
        }
    }

    /**
     * Assign an array as the template's data.
     * Usually set from the View.
     *
     * @param array $data
     *
     * @return $this
     */
    public function assign(array $data = [])
    {
        $this->data = array_merge($data, $this->data);
        return $this;
    }

    /**
     * Render the template.
     *
     * @param array $data
     */
    public function render(array $data = [])
    {
        $this->data = array_merge($data, $this->data);
        require $this->path;
    }
}