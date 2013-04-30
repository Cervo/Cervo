<?php

/**
 *
 * Copyright (c) 2013 Marc André "Manhim" Audet <root@manhim.net>. All rights reserved.
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
 * DISCLAIMED. IN NO EVENT SHALL MARC ANDRÉ "MANHIM" AUDET BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */



namespace Cervo\Libraries;



class Router
{
    protected $stringpath = '';
    protected $arraypath = [];
    protected $routes = [];

    public function __construct()
    {
        $this->stringpath = trim($this->parseRoute(), '/');

        while (strpos($this->stringpath, '//') !== false)
            $this->stringpath = str_replace('//', '/', $this->stringpath);

        $this->arraypath = ($this->stringpath == '' ? [] : explode('/', $this->stringpath));

        $this->route();
    }

    public function addRoute($stringpath, $module, $controller, $method)
    {
        $this->routes[] = new \Cervo\Libraries\RouterPath($stringpath, $module, $controller, $method);
    }

    public function getRoute()
    {
        $current = [];
        $weak_current = [];

        foreach ($this->routes as $r)
        {
            $result = $r->compare($this->arraypath);

            if ($result >= 0)
            {
                $weak_current[] = ['result' => $r, 'precision' => $result];
            }
            else if ($result === RouterPath::FULL_MATCH)
            {
                $current[] = $r;
            }
        }

        $c_current = count($current);
        $c_weak_current = count($weak_current);

        if ($c_current == 1)
        {
            return current($current);
        }
        else if ($c_current > 1)
        {
            throw new \Cervo\Libraries\Exceptions\TooManyRoutesException();
        }
        else
        {
            if ($c_weak_current == 1)
            {
                return current($weak_current)['result'];
            }
            else if ($c_weak_current > 1)
            {
                $highest = 0;
                $highest_weaks = [];

                foreach ($weak_current as $w)
                {
                    if ($w['precision'] > $highest)
                    {
                        $highest = $w['precision'];
                        $highest_weaks = [$w];
                    }
                    else if ($w['prevision'] == $highest)
                    {
                        $highest_weaks[] = $w;
                    }
                }

                if (count($highest_weaks) == 1)
                {
                    return current($highest_weaks)['result'];
                }
                else
                {
                    throw new \Cervo\Libraries\Exceptions\TooManyWeakRoutesException();
                }
            }
            else
            {
                throw new \Cervo\Libraries\Exceptions\RouteNotFoundException();
            }
        }
    }

    public function getArrayPath()
    {
        return $this->arraypath;
    }

    protected function parseRoute()
    {
        if (defined('STDIN'))
        {
            $args = array_slice($_SERVER['argv'], 1);
            return $args ? '/' . implode('/', $args) : '';
        }

        if ($uri = $this->detectUri())
        {
            return $uri;
        }

        $path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : getenv('PATH_INFO');

        if (trim($path, '/') != '' && $path != "/" . SELF)
        {
            return $path;
        }

        $path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : getenv('QUERY_STRING');

        if (trim($path, '/') != '')
        {
            return $path;
        }

        return '';
    }

    protected function detectUri()
    {
        if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME']))
        {
            return '';
        }

        $uri = $_SERVER['REQUEST_URI'];

        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
        {
            $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        }
        elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
        {
            $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        }

        if (strncmp($uri, '?/', 2) === 0)
        {
            $uri = substr($uri, 2);
        }

        $parts = preg_split('#\?#i', $uri, 2);
        $uri = $parts[0];

        if (isset($parts[1]))
        {
            $_SERVER['QUERY_STRING'] = $parts[1];
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }
        else
        {
            $_SERVER['QUERY_STRING'] = '';
            $_GET = [];
        }

        if ($uri == '/' || empty($uri))
        {
            return '/';
        }

        $uri = parse_url($uri, PHP_URL_PATH);

        return str_replace([
            '//',
            '../'
        ], '/', trim($uri, '/'));
    }

    protected function route()
    {
        foreach (glob(APATH . '*' . DS . 'Router.php', \GLOB_NOSORT | \GLOB_NOESCAPE) as $file)
        {
            require $file;
        }
    }
}
