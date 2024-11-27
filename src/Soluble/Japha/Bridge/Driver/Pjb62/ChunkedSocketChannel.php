<?php

declare(strict_types=1);
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

use Soluble\Japha\Bridge\Exception\RuntimeException;
use Soluble\Japha\Bridge\Driver\Pjb62\Exception\BrokenConnectionException;
use Soluble\Japha\Bridge\Exception;

class ChunkedSocketChannel extends SocketChannel
{
    /**
     * @throws Exception\RuntimeException
     */
    public function fwrite(string $data): int
    {
        $len = dechex(strlen($data));
        $written = fwrite($this->peer, "{$len}\r\n{$data}\r\n");
        if (!$written) {
            $msg = 'Cannot write to socket';
            throw new RuntimeException($msg);
        }

        return $written;
    }

    /**
     * @throws BrokenConnectionException
     */
    public function fread(int $size): ?string
    {
        $line = fgets($this->peer, $this->recv_size);
        if ($line === false) {
            throw new BrokenConnectionException(
                'Cannot read from socket'
            );
        }

        $length = (int) hexdec($line);
        $data = '';
        while ($length > 0) {
            $str = fread($this->peer, $length);
            if (feof($this->peer) || $str === false) {
                return null;
            }
            
            $length -= strlen($str);
            $data .= $str;
        }
        
        fgets($this->peer, 3);

        return $data;
    }

    public function keepAlive(): void
    {
        $this->keepAliveSC();
        $this->checkE();
        fclose($this->peer);
    }
}
