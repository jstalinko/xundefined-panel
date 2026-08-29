<?php

test('the application returns a successful response for landing page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('XUNDEFINED');
    $response->assertSee('XingZhang Labs.');
});
