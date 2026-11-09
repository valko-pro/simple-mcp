<?php
/**
 * safe_delete must trash every post type, not only posts and pages.
 * Run from the site root: wp eval-file web/app/plugins/simple-mcp/tests/safe-delete-trash.php --user=1
 * Creates one TEST post per type and deletes it at the end.
 */

function smcp_test_result(array $response): array {
    return json_decode($response['content'][0]['text'] ?? '{}', true) ?: [];
}

function smcp_test_check(string $label, bool $passed): void {
    echo ($passed ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;

    if (!$passed) {
        $GLOBALS['smcp_test_failed'] = true;
    }
}

foreach (['post', 'product', 'product_variation'] as $post_type) {
    $id = wp_insert_post([
        'post_title'  => 'TEST safe_delete',
        'post_type'   => $post_type,
        'post_status' => 'publish',
    ]);

    $first = Simple_MCP_Tools_Content::safe_delete(['post_id' => $id]);
    clean_post_cache($id);
    smcp_test_check("$post_type: reports trashed", (smcp_test_result($first)['trashed'] ?? null) === true);
    smcp_test_check("$post_type: row is still there, in the trash", get_post_status($id) === 'trash');

    $second = Simple_MCP_Tools_Content::safe_delete(['post_id' => $id]);
    clean_post_cache($id);
    smcp_test_check("$post_type: second call refuses", !empty($second['isError']));
    smcp_test_check("$post_type: second call keeps the row", get_post_status($id) === 'trash');

    $forced = Simple_MCP_Tools_Content::safe_delete(['post_id' => $id, 'force' => true]);
    clean_post_cache($id);
    smcp_test_check("$post_type: force reports not trashed", (smcp_test_result($forced)['trashed'] ?? null) === false);
    smcp_test_check("$post_type: force removes the row", get_post($id) === null);
}

exit(empty($GLOBALS['smcp_test_failed']) ? 0 : 1);
