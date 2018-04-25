<?php
/**
 * RequestTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

/** @noinspection PhpUnusedLocalVariableInspection */

use Apine\Http\Request;
use Apine\Http\UploadedFile;
use Apine\Http\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RequestTest extends TestCase
{
    
    public function testConstructor() : Request
    {
        $uri = new Uri('http://example.com/test/23?test=123');
        $request = new Request(
            'GET',
            $uri
        );
        
        $this->assertAttributeEquals('GET', 'method', $request);
        $this->assertAttributeInstanceOf(UriInterface::class, 'uri', $request);
        $this->assertAttributeInstanceOf(StreamInterface::class, 'body', $request);
        $this->assertArrayHasKey('host', $this->getObjectAttribute($request, 'headers'));
        
        return $request;
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid resource type: object
     */
    public function testConstructorInvalidBody()
    {
        $request = new Request(
            'GET',
            'https://example.com',
            [],
            new Uri('google.com')
        );
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testAddsHostHeader(Request $request)
    {
        $this->assertEquals('example.com', $request->getHeaderLine('Host'));
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetRequestTarget(Request $request)
    {
        $this->assertEquals('/test/23?test=123', $request->getRequestTarget());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testWithRequestTarget(Request $request)
    {
        $request = $request->withRequestTarget('/giza?page=123');
        $this->assertEquals('/giza?page=123', $request->getRequestTarget());
    }
    
    public function testGetRequestTargetEmpty()
    {
        $request = new Request(
            'GET',
            'https://example.com'
        );
        
        $this->assertEquals('/', $request->getRequestTarget());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid target provided. Request targets may not contain whitespaces.
     */
    public function testWithRequestTargetInvalid(Request $request)
    {
        $request->withRequestTarget('/trest asdfas');
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetMethod(Request $request)
    {
        $this->assertEquals('GET', $request->getMethod());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testWithMethod(Request $request)
    {
        $request = $request->withMethod('POST');
        $this->assertEquals('POST', $request->getMethod());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetUri(Request $request)
    {
        $uri = $request->getUri();
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertEquals('example.com', $uri->getHost());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testWithUri(Request $request)
    {
        $uri = new Uri('https://google.ca');
        $request = $request->withUri($uri);
        $this->assertInstanceOf(Uri::class, $request->getUri());
        $this->assertEquals($uri, $request->getUri());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetServerParams(Request $request)
    {
        $this->assertEquals([], $request->getServerParams());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetCookieParams(Request $request)
    {
        $this->assertEquals([], $request->getCookieParams());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testWithCookieParams(Request $request)
    {
        $array = ['cookie' => 'value'];
        $request = $request->withCookieParams($array);
        
        $this->assertEquals($array, $request->getCookieParams());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetQueryParams(Request $request)
    {
        $this->assertEquals(['test' => 123], $request->getQueryParams());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testWithQueryParams(Request $request)
    {
        $array = ['query' => 'test', 'test' => 5678];
        $request = $request->withQueryParams($array);
        $this->assertEquals($array, $request->getQueryParams());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetUploadedFiles(Request $request)
    {
        $this->assertEquals([], $request->getUploadedFiles());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testWithUploadedFiles(Request $request)
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'test');
        $file = new UploadedFile($resource, 4, 0, 'text.txt', 'text/plain');
        
        $request = $request->withUploadedFiles([$file]);
        $this->assertEquals([$file], $request->getUploadedFiles());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testGetParsedBody(Request $request)
    {
        $this->assertEquals(null, $request->getParsedBody());
    }
    
    public function testGetParsedBodyJson()
    {
        $json_array = [
            'title' => 'value',
            'array' => [
                1,
                2
            ]
        ];
        $json_string = json_encode($json_array);
        
        $request = new Request(
            'POST',
            'http://example.com/test',
            ['Content-Type' => 'application/json'],
            $json_string
        );
        
        $this->assertEquals($json_array, $request->getParsedBody());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testWithParsedBody(Request $request)
    {
        $array = ['one' => 1, 'two' => 2, 'three' => 3];
        $request = $request->withParsedBody($array);
        $this->assertEquals($array, $request->getParsedBody());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     *
     * @return \Apine\Http\Request
     */
    public function testWithAttribute(Request $request)
    {
        $request = $request->withAttribute('name', 'value');
        $this->assertAttributeEquals(['name' => 'value'], 'attributes', $request);
        
        return $request;
    }
    
    /**
     * @depends testWithAttribute
     * @param \Apine\Http\Request $request
     */
    public function testGetAttributes(Request $request)
    {
        $this->assertInternalType('array', $request->getAttributes());
        $this->assertEquals(['name' => 'value'], $request->getAttributes());
    }
    
    /**
     * @depends testWithAttribute
     * @param \Apine\Http\Request $request
     */
    public function testGetAttribute(Request $request)
    {
        $this->assertEquals('value', $request->getAttribute('name'));
    }
    
    /**
     * @depends testWithAttribute
     * @param \Apine\Http\Request $request
     */
    public function testGetAttributeNonExisting(Request $request)
    {
        $this->assertEquals(null, $request->getAttribute('title'));
    }
    
    /**
     * @depends testWithAttribute
     * @param \Apine\Http\Request $request
     */
    public function testGetAttributeNonExistingWithDefault(Request $request)
    {
        $this->assertEquals('default', $request->getAttribute('none', 'default'));
    }
    
    /**
     * @depends testWithAttribute
     * @param \Apine\Http\Request $request
     */
    public function testWithoutAttribute(Request $request)
    {
        $request = $request->withoutAttribute('name');
        $this->assertInternalType('array', $request->getAttributes());
        $this->assertArrayNotHasKey('name', $request->getAttributes());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testIsHttps(Request $request)
    {
        $this->assertFalse($request->isHttps());
    
        $request = new Request(
            'GET',
            'https://example.com'
        );
        
        $this->assertTrue($request->isHttps());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testIsAjax(Request $request)
    {
        $this->assertFalse($request->isAjax());
        
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->isAjax());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testIsGet(Request $request)
    {
        $this->assertTrue($request->isGet());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testIsPost(Request $request)
    {
        $request = $request->withMethod('POST');
        $this->assertTrue($request->isPost());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testIsPut(Request $request)
    {
        $request = $request->withMethod('PUT');
        $this->assertTrue($request->isPut());
    }
    
    /**
     * @depends testConstructor
     * @param \Apine\Http\Request $request
     */
    public function testIsDelete(Request $request)
    {
        $request = $request->withMethod('DELETE');
        $this->assertTrue($request->isDelete());
    }
}
