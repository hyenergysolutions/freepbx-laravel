## FreePBX Laravel

This package provides integration with FreePBX's GraphQL and REST APIs for managing PBX systems.

### Configuration

Requires these environment variables:
- `FREEPBX_URL` - Base URL of FreePBX (e.g., `http://192.168.1.100:83`)
- `FREEPBX_CLIENT_ID` - OAuth client ID from FreePBX Admin > API > Applications
- `FREEPBX_CLIENT_SECRET` - OAuth client secret

### Available Methods

@verbatim
<code-snippet name="FreePBX Facade Usage" lang="php">
use HyEnergySolutions\FreePBX\Facades\FreePBX;

// Get all extensions with user details
$extensions = FreePBX::getExtensions();

// Get all ring groups
$ringGroups = FreePBX::getRingGroups();

// Get call detail records (default 100, configurable)
$cdrs = FreePBX::getCdrs(50);

// Get day/night call flows
$callFlows = FreePBX::getCallFlows();
</code-snippet>
@endverbatim

### Return Types

All methods return `Illuminate\Support\Collection`:

@verbatim
<code-snippet name="Working with Results" lang="php">
$extensions = FreePBX::getExtensions();

// Filter extensions
$pjsipExtensions = $extensions->where('tech', 'pjsip');

// Get extension names
$names = $extensions->pluck('user.name');

// Find specific extension
$ext = $extensions->firstWhere('extensionId', '1001');
</code-snippet>
@endverbatim

### Error Handling

@verbatim
<code-snippet name="Error Handling" lang="php">
use HyEnergySolutions\FreePBX\Exceptions\FreePBXException;

try {
    $extensions = FreePBX::getExtensions();
} catch (FreePBXException $e) {
    // Handle token failures, GraphQL errors, or REST API errors
    Log::error('FreePBX error: ' . $e->getMessage());
}
</code-snippet>
@endverbatim

### Features

- **OAuth Token Caching**: Tokens are cached for ~58 minutes automatically
- **Retry Logic**: Requests retry 3 times with 100ms delay on transient failures
- **Timeout**: 30-second timeout prevents hanging requests
- **GraphQL & REST**: Uses GraphQL for extensions/ringgroups/CDRs, REST for call flows
