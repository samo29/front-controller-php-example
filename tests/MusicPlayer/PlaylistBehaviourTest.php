<?php
namespace tests\MusicPlayer;

use tests\BaseWebTestClass;
use \Guzzle\Http\Exception\BadResponseException;

/**
 * Class PlaylistBehaviourTest
 * @package tests\MusicPlayer
 *
 * Here we going to test playlist API
 */
class PlaylistBehaviourTest extends BaseWebTestClass
{

    /**
     * Test authentication failure
     *
     * @covers \MusicPlayer\MusicPlayerAuthController::execute
     */
    public function testAuthFailure()
    {
        /**
         * Accessing private API without authentication token should ended up with an error
         */
        try {
            $this->client->get('api/playlist', ['Accept' => 'application/json'])->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 401, 'Status of response should be 401!');
        }

        /**
         * Accessing private API with wrong authentication token should ended up with an error
         */
        try {
            $this->client->get('api/playlist', ['Accept' => 'application/json', 'token' => 'qwerty'])->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 401, 'Status of response should be 401!');

        }
    }

    /**
     * Test authentication success
     *
     * @covers \MusicPlayer\controllers\PlaylistController::getPlaylist
     */
    public function testAuthSuccess()
    {
        $request = $this->client->get('api/playlist', $this->getAuthHeaders());
        $response = $request->send();
        $this->assertEquals($response->getStatusCode(), 200, 'Status of response should be 200!');
    }

    /**
     * Test possibility of adding new playlist and getting playlist
     *
     * @depends testAuthSuccess
     *
     * @covers \MusicPlayer\controllers\PlaylistController::addPlaylist
     */
    public function testAddingNewPlaylistAndGet()
    {
        $authHeaders = $this->getAuthHeaders();

        /**
         * Lets try to post without data, we should get error
         */
        try {
            $this->client
                ->post('api/playlist', $authHeaders)
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 400, 'Status of response should be 400!');
        }

        /**
         * Now we should create new playlist
         */
        $playlistName = 'New Test Playlist';
        $request = $this->client->post('api/playlist', $authHeaders, ['name' => $playlistName]);
        $response = $request->send();
        $decodedResponse = $response->json();
        $this->assertEquals($response->getStatusCode(), 201, 'Status of response should be 201!');
        $this->assertTrue(isset($decodedResponse['playlist']) && isset($decodedResponse['playlist']['id'])
            , 'Data regarding playlist should be presented!');

        $playlistId = $decodedResponse['playlist']['id'];

        /**
         * For duplication of creation request we should get error
         */
        try {
            $this->client
                ->post('api/playlist', $authHeaders, ['name' => $playlistName])
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 400, 'Status of response should be 400!');
        }

        /**
         * Since we created playlist, we should get list of playlist
         */
        $request = $this->client->get('api/playlist', $authHeaders);
        $response = $request->send();
        $decodedResponse = $response->json();
        $this->assertEquals($response->getStatusCode(), 200, 'Status of response should be 200!');
        $this->assertTrue(isset($decodedResponse['playlist']) && count($decodedResponse['playlist']) > 0
            , 'Data regarding playlist should be presented!');

        /**
         * We should get data about our new playlist
         */
        $request = $this->client->get('api/playlist/' . $playlistId, $authHeaders);
        $response = $request->send();
        $decodedResponse = $response->json();
        $this->assertEquals($response->getStatusCode(), 200, 'Status of response should be 200!');
        $this->assertTrue(isset($decodedResponse['playlist']) && isset($decodedResponse['playlist']['name'])
            , 'Data regarding playlist should be presented!');
        $this->assertTrue($decodedResponse['playlist']['name'] == $playlistName, 'Expected names should be equal!');
    }

    /**
     * Test playlist update
     *
     * @depends testAddingNewPlaylistAndGet
     *
     * @covers \MusicPlayer\controllers\PlaylistController::updatePlaylist
     */
    public function testUpdatePlaylist()
    {
        $authHeaders = $this->getAuthHeaders();

        /**
         * Now we should create new playlist
         */
        $playlistName = 'New Test Playlist';
        $response = $this->client
            ->post('api/playlist', $authHeaders, ['name' => $playlistName])
            ->send();
        $decodedResponse = $response->json();

        $playlistId = $decodedResponse['playlist']['id'];

        /**
         * Lets try to update without proper data, we should get error
         */
        try {
            $this->client
                ->put('api/playlist/' . $playlistId, $authHeaders)
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 400, 'Status of response should be 400!');
        }

        /**
         * Lets try to update existing playlist for wrong user
         */
        try {
            $decodedResponse = $this->client
                ->post('api/users/authentication', ['Accept' => 'application/json'])
                ->send()
                ->json();
            $this->client
                ->put('api/playlist/' . $playlistId, ['Accept' => 'application/json', 'token' => $decodedResponse['token']]
                    , ['newName' => '1'])
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 404, 'Status of response should be 404!');
        }

        /**
         * Lets update new playlist
         */
        $newPlaylistName = 'Updated Test Playlist';
        $request = $this->client->put('api/playlist/' . $playlistId, $authHeaders, ['newName' => $newPlaylistName]);
        $response = $request->send();
        $this->assertEquals($response->getStatusCode(), 204, 'Status of response should be 204!');

        /**
         * We should get updated playlist
         */
        $decodedResponse = $this->client
            ->get('api/playlist/' . $playlistId, $authHeaders)
            ->send()
            ->json();
        $this->assertTrue($decodedResponse['playlist']['name'] == $newPlaylistName, 'Expected new names should be equal!');

        /**
         * Lets create second playlist
         */
        $playlistName = 'New Test Playlist';
        $response = $this->client
            ->post('api/playlist', $authHeaders, ['name' => $playlistName])
            ->send();
        $decodedResponse = $response->json();

        $playlistId = $decodedResponse['playlist']['id'];

        /**
         * Lets try to update existing playlist with the same name as we already have, should get error
         */
        try {
            $newPlaylistName = 'Updated Test Playlist';
            $this->client->put('api/playlist/' . $playlistId, $authHeaders, ['newName' => $newPlaylistName])->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 400, 'Status of response should be 400!');
        }
    }

    /**
     * Test playlist deletion
     *
     * @depends testAddingNewPlaylistAndGet
     *
     * @covers \MusicPlayer\controllers\PlaylistController::deletePlaylist
     */
    public function testPlaylistDeletion()
    {
        $authHeaders = $this->getAuthHeaders();

        /**
         * Now we should create new playlist
         */
        $playlistName = 'New Test Playlist';
        $response = $this->client
            ->post('api/playlist', $authHeaders, ['name' => $playlistName])
            ->send();
        $decodedResponse = $response->json();

        $playlistId = $decodedResponse['playlist']['id'];

        /**
         * Lets try to delete existing playlist for wrong user
         */
        try {
            $decodedResponse = $this->client
                ->post('api/users/authentication', ['Accept' => 'application/json'])
                ->send()
                ->json();
            $this->client
                ->delete('api/playlist/' . $playlistId, ['Accept' => 'application/json', 'token' => $decodedResponse['token']])
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 404, 'Status of response should be 404!');
        }

        /**
         * Lets delete playlist
         */
        $request = $this->client->delete('api/playlist/' . $playlistId, $authHeaders);
        $response = $request->send();
        $this->assertEquals($response->getStatusCode(), 204, 'Status of response should be 204!');

        /**
         * For duplication of deletion request we should get error
         */
        try {
            $this->client
                ->delete('api/playlist/' . $playlistId, $authHeaders)
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 404, 'Status of response should be 404!');
        }

        /**
         * We should get empty response
         */
        $decodedResponse = $this->client
            ->get('api/playlist/' . $playlistId, $authHeaders)
            ->send()
            ->json();
        $this->assertTrue(empty($decodedResponse['playlist']));

        /**
         * We should get empty list of playlist
         */
        $decodedResponse = $this->client
            ->get('api/playlist', $authHeaders)
            ->send()
            ->json();
        $this->assertTrue(empty($decodedResponse['playlist']));
    }

    /**
     * Test possibility of adding new playlist and getting playlist
     *
     * @depends testAddingNewPlaylistAndGet
     *
     * @covers \MusicPlayer\controllers\PlaylistController::addSongToPlaylist
     */
    public function testAddingSongToPlaylistAndGet()
    {
        $authHeaders = $this->getAuthHeaders();

        /**
         * Lets create new playlist
         */
        $playlistName = 'New Test Playlist with Songs';
        $decodedResponse = $this->client
            ->post('api/playlist', $authHeaders, ['name' => $playlistName])
            ->send()
            ->json();

        $playlistId = $decodedResponse['playlist']['id'];

        /**
         * Lets try to add song to playlist without put data, should get error
         */
        try {
            $this->client->put('api/playlist/' . $playlistId . '/song', $authHeaders)->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 400, 'Status of response should be 400!');
        }

        /**
         * Lets add new song to playlist
         */
        $track = 'New Track';
        $artist = 'New Artist';
        $album = 'New Album';

        $data = ['track' => $track, 'artist' => $artist, 'album' => $album];

        $request = $this->client->put('api/playlist/' . $playlistId . '/song', $authHeaders, $data);
        $response = $request->send();
        $decodedResponse = $response->json();
        $this->assertEquals($response->getStatusCode(), 201, 'Status of response should be 201!');
        $this->assertTrue(isset($decodedResponse['song']) && isset($decodedResponse['song']['id'])
            , 'Data regarding song should be presented!');

        /**
         * Lets try to do double add of existing song to playlist, should get error
         */
        try {
            $this->client->put('api/playlist/' . $playlistId . '/song', $authHeaders, $data)->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 400, 'Status of response should be 400!');
        }
    }

    /**
     * Test playlist deletion
     *
     * @depends testAddingNewPlaylistAndGet
     *
     * @covers \MusicPlayer\controllers\PlaylistController::deleteSongFromPlaylist
     */
    public function testDeleteSongFromPlaylist()
    {
        $authHeaders = $this->getAuthHeaders();

        /**
         * Now we should create new playlist
         */
        $playlistName = 'New Test Playlist with Songs';
        $response = $this->client
            ->post('api/playlist', $authHeaders, ['name' => $playlistName])
            ->send();
        $decodedResponse = $response->json();

        $playlistId = $decodedResponse['playlist']['id'];

        /**
         * Lets add new song to playlist
         */
        $track = 'New Track';
        $artist = 'New Artist';
        $album = 'New Album';

        $data = ['track' => $track, 'artist' => $artist, 'album' => $album];

        $decodedResponse = $this->client
            ->put('api/playlist/' . $playlistId . '/song', $authHeaders, $data)
            ->send()
            ->json();
        $songId = $decodedResponse['song']['id'];

        /**
         * Lets try to delete song from existing playlist for wrong user
         */
        try {
            $decodedResponse = $this->client
                ->post('api/users/authentication', ['Accept' => 'application/json'])
                ->send()
                ->json();
            $this->client
                ->delete('api/playlist/' . $playlistId . '/song/' . $songId, ['Accept' => 'application/json', 'token' => $decodedResponse['token']])
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 404, 'Status of response should be 404!');
        }

        /**
         * Lets delete song from playlist
         */
        $request = $this->client->delete('api/playlist/' . $playlistId . '/song/' . $songId, $authHeaders);
        $response = $request->send();
        $this->assertEquals($response->getStatusCode(), 204, 'Status of response should be 204!');

        /**
         * For duplication of deletion request we should get error
         */
        try {
            $this->client
                ->delete('api/playlist/' . $playlistId . '/song/' . $songId, $authHeaders)
                ->send();
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 404, 'Status of response should be 404!');
        }
    }
}