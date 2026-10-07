import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { Agent, CursorAgentError, FileCredentialStore } from "@cursor/sdk";

const [nodeMajor, nodeMinor] = process.versions.node.split(".").map(Number);
if (nodeMajor < 22 || (nodeMajor === 22 && nodeMinor < 13)) {
  console.error(
    `Node ${process.versions.node} is too old. This audit needs Node >= 22.13.`,
  );
  process.exit(1);
}

const repoRoot = resolve(dirname(fileURLToPath(import.meta.url)), "../..");

async function resolveApiKey() {
  const fromEnv = process.env.CURSOR_API_KEY?.trim();
  if (fromEnv) return fromEnv;
  const stored = await new FileCredentialStore().load();
  return stored?.apiKey?.trim() ?? "";
}

const prompt = `Audit this Drupal enterprise starter against the contract in README.md.

The README says the project showcases:
- custom modules
- REST APIs
- access control
- caching
- automated tests
- CI/CD

Read the working tree. Write only docs/starter-gap.md.

For each capability above, state whether it is present and cite the file paths that implement it. When a capability is absent, say so and name the conventional path where it would live (for example web/modules/custom or .github/workflows). Keep the report short enough to scan.

Do not create modules, tests, workflows, Composer files, or any file other than docs/starter-gap.md. If that report already exists, replace it.`;

const apiKey = await resolveApiKey();
if (!apiKey) {
  console.error(
    "Missing CURSOR_API_KEY and no SDK login at ~/.cursor/sdk/auth.json. Mint a key at https://cursor.com/dashboard/cloud-agents or run Cursor.auth.login().",
  );
  process.exit(1);
}

try {
  const result = await Agent.prompt(prompt, {
    apiKey,
    model: { id: "composer-2" },
    local: { cwd: repoRoot, settingSources: [] },
  });

  console.log(`[audit] run=${result.id} status=${result.status}`);
  if (result.result) {
    console.log(result.result);
  }

  if (result.status === "finished") {
    process.exit(0);
  }
  if (result.status === "cancelled") {
    process.exit(2);
  }
  console.error(`[audit] run ${result.id} ended as ${result.status}`);
  process.exit(2);
} catch (err) {
  if (err instanceof CursorAgentError) {
    console.error(
      `[audit] startup failed: ${err.message} retryable=${err.isRetryable}`,
    );
    process.exit(err.isRetryable ? 75 : 1);
  }
  throw err;
}
