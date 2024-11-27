<?php
/**
 * soluble-japha / PHPJavaBridge driver client.
 *
 * Refactored version of phpjababridge's Java.inc file compatible
 * with php java bridge 6.2
 *
 *
 * @credits   http://php-java-bridge.sourceforge.net/pjb/
 *
 * @see      http://github.com/belgattitude/soluble-japha
 *
 * @author Jost Boekemeier
 * @author Vanvelthem Sébastien (refactoring and fixes from original implementation)
 * @license   MIT
 *
 * The MIT License (MIT)
 * Copyright (c) 2014-2017 Jost Boekemeier
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Soluble\Japha\Bridge\Driver\Pjb62;

use Soluble\Japha\Bridge\Driver\Pjb62\Exception\BrokenConnectionException;

class HttpTunnelHandler extends SimpleHttpTunnelHandler
{
    /**
     * @throws BrokenConnectionException
     */
    public function fread(int $size): ?string
    {
        if ($this->hasContentLength) {
            $data = fread($this->socket, $this->headers['content_length']);
            if ($data === false) {
                throw new BrokenConnectionException(
                    'Cannot read from socket.'
                );
            }

            return $data;
        }
        return parent::fread($size);
    }

    /**
     * @throws BrokenConnectionException
     */
    public function fwrite(string $data): ?int
    {
        if ($this->hasContentLength) {
            $return = fwrite($this->socket, $data);
            if ($return === false) {
                throw new BrokenConnectionException(
                    'Cannot write from socket.'
                );
            }
        }
        
        return parent::fwrite($data);
    }

    protected function close(): void
    {
        if ($this->hasContentLength) {
            fwrite($this->socket, "0\r\n\r\n");
            fclose($this->socket);
        } else {
            parent::close();
        }
    }
}
