<?php

$url = 'https://neo4j.com/docs/bolt/current/bolt-compatibility/';
$fallbackUrl = 'https://raw.githubusercontent.com/neo4j-drivers/docs-bolt/main/modules/ROOT/pages/bolt-compatibility.adoc';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0 (GitHubActions; BoltCompatibilityChecker)\r\n",
        'timeout' => 15,
    ],
]);

$content = @file_get_contents($url, false, $context);
$versions = [];

if ($content !== false) {
    preg_match_all('/Bolt\s+<code>([0-9]+(?:\.[0-9]+)?)<\/code>/i', $content, $matches);
    $versions = array_unique($matches[1] ?? []);
}

if (empty($versions)) {
    echo "Falling back to raw docs-bolt repository...\n";
    $content = @file_get_contents($fallbackUrl, false, $context);
    if ($content !== false) {
        preg_match_all('/\|\s*Bolt\s+`([0-9]+(?:\.[0-9]+)?)`/i', $content, $matches);
        $versions = array_unique($matches[1] ?? []);
    }
}

if (empty($versions)) {
    fwrite(STDERR, "Could not fetch or parse Bolt versions from documentation!\n");
    exit(1);
}

echo "Detected versions in documentation: " . implode(', ', $versions) . "\n";

// Known exceptions: 5.5 was never released/supported by Neo4j server
$ignored = ['5.5'];

$newVersions = [];
foreach ($versions as $version) {
    if (in_array((string)$version, $ignored, true)) {
        continue;
    }

    $parts = explode('.', (string)$version);
    $major = $parts[0];
    $minor = $parts[1] ?? '0';
    $className = ($minor === '0') ? "V{$major}" : "V{$major}_{$minor}";
    $classPath = __DIR__ . "/../../src/protocol/{$className}.php";

    if (!file_exists($classPath)) {
        $newVersions[] = (string)$version;
    }
}

if (empty($newVersions)) {
    echo "All detected Bolt versions are already supported in src/protocol.\n";
} else {
    echo "Found unsupported Bolt version(s): " . implode(', ', $newVersions) . "\n";
}

$githubOutput = getenv('GITHUB_OUTPUT');
if ($githubOutput && file_exists($githubOutput)) {
    file_put_contents($githubOutput, "new_versions=" . json_encode(array_values($newVersions)) . "\n", FILE_APPEND);
}
