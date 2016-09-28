<?php

namespace Brnjna\MorrSnippets;

function get_release_data_url($release_uuid) {
  return RELEASE_DATA_BASE_URL . $release_uuid;
}

/*
 * Returns Basic Release Data as returned by the morrmusic API.
 *
 * The response is structured like this:
   {
    metadata: {
      gdid: "4411",
      uuid: "9ba60ae1-b139-48ee-a617-d97260ce19e2",
      title: "Take Off!",
      display_artist: "Andromeda Mega Express Orchestra",
      uuid_label: "604e2791-4853-443a-a670-ddf21f2438cf",
      is_compilation: "0",
      amg_id: "P 1069171",
      release_date_digital: null,
      genre_primary: "Jazz",
      genre_secondary: "Alternative",
      info_ger: "",
      info_en: ""
    },
    formats: [],
    labels: {},
    tags: {},
    format_types: {},
    playlists: {},
    tracks: {
      30ad65fa-05a3-4803-a9d1-7751305cb84a: {
        gdid: "8166",
        uuid: "30ad65fa-05a3-4803-a9d1-7751305cb84a",
        display_artist: "Andromeda Mega Express Orchestra",
        title: "Milky Way Fables",
        duration: "12:38",
        isrc: "DEZ750500046",
        version: null,
        composer: "D. Glatzel",
        author: null,
        arranger: null,
        publisher: "D. Glatzel",
        producer: "Copyright Control",
        copyright_year: "2009",
        copyright: "alien transistor",
        explicit_lyrics: "0",
        extention: "wav",
        audio_source_valid: "1",
        quality: "2",
        main_artist: [
          "a405b4cb-e5d7-4a74-ac0c-d68069663047"
        ]
      }
    },
    artists: {}
   }
 */
function get_release_data($release_uuid) {
  $url = get_release_data_url($release_uuid);

  // cache results
  $transient_key = 'morr-release-' . md5($url . '&c=' . API_CACHE_STRING);
  $data = get_transient($transient_key);

  if ($data === false) {
    // debug_log('no cache entry, have to fetch ' . $url . ' again.');

    $response = wp_remote_get($url, array(
      'user-agent'  => USER_AGENT,
      'sslverify'   => false
    ));

    try {
      $data = is_array($response) ? json_decode($response['body'], true) : null;
    } catch ( Exception $ex ) {
      $data = null;
    }

    if ($data != null) {
      set_transient($transient_key, $data, API_CACHE_TTL);
    }
  }

  // debug_log('release data found for release ID "'.$release_uuid.'"');

  return $data;
}


/*
 * Returns an Array of Track objects.
 *
 * Track object:
   {
      uuid,
      title,
      artist,
      duration,
      snippet_url
   }
 */
function get_tracks($release_data) {
  $tracks = array();
  if ($release_data) {
    $playlist = _get_primary_playlist($release_data);

    if ($playlist) {
      $all_tracks = $release_data['tracks'];

      foreach ($playlist['tracks'] as $playlist_track) {
        $track_data = $all_tracks[$playlist_track['uuid_track']];

        if ($track_data) {
          $track = array(
            'uuid' => $track_data['uuid'],
            'title' => $track_data['title'],
            'version' => $track_data['version'],
            'artist' => $track_data['display_artist'],
            'duration' => $track_data['duration'],
            'snippet_url' => SNIPPET_BASE_URL . $track_data['uuid'] . '.mp3'
          );

          array_push($tracks, $track);
        }
      }
    }
  }
  return $tracks;
}


/*
 * Returns the primary playlist of the given release
 */
function _get_primary_playlist($release_data) {
  if (count($release_data['playlists'])) {
    if (count($release_data['playlists']) == 1) {
      return array_values($release_data['playlists'])[0];
    } else {
      $primary_format = _get_primary_format($release_data);

      if ($primary_format && $primary_format['uuid_playlist']) {
        $playlist_uuid = $primary_format['uuid_playlist'];
        return $release_data['playlists'][$playlist_uuid];
      } else { // no primary format => take first playlist
        return array_values($release_data['playlists'])[0];
      }
    }
  }
}

function _get_primary_format($release_data) {
  if (count($release_data['formats']) == 1) {
    return array_values($release_data['formats'])[0];
  } else {
    $primary_formats = array_filter($release_data['formats'], function($format) {
      return $format['is_primary_format'];
    });
    return count($primary_formats) > 0 ?
      array_values($primary_formats)[0] : array_values($release_data['formats'])[0];
  }
}


/*
 * Returns Image URL for full resolution front cover
 */
function get_cover_url($release_uuid) {
  return COVER_BASE_URL . $release_uuid . '.jpg';
}
