# ActioGlobal_test_task

Context: WWAALL is a multi-tenant B2B SaaS. Below are three short, fictionalized
scenarios from our stack. For each, identify the problems and propose a fix written
response, code welcome but not required.
Budget: ~2 hours total. If you only had time for one, prioritize
Scenario 1 — Backend / Domain (45-60 min)
namespace App\Domain\KeyResult;
use App\Models\KeyResult;
use App\Models\Workspace;
class KeyResultProgressUpdater
{
public function updateProgress(int $keyResultId, float $newValue): KeyResult
$keyResult = KeyResult::find($keyResultId);
if ($newValue < 0 || $newValue > $keyResult->target) {
throw new \InvalidArgumentException('Invalid progress value');
$keyResult->current
_
value = $newValue;
$keyResult->save();
Cache::put("kr
_progress
_{$keyResultId}"
, $newValue, 3600);
return $keyResult;
}
Questions: What problems do you see? What's the concrete production risk of each?
If you could only fix one before this shipped, which one, and why?
Scenario 2 — Infra / DevOps (30-40 min)
# bitbucket-pipelines.yml (excerpt)
deploy-production:
- step:
name: Deploy to ECS
script:
- export AWS
SECRET
ACCESS
KEY=AKIAxxxxxxxxxxxxxxxx
_
_
_
- aws ecs update-service --cluster prod --service wwaall-api
--force-new-deployment
- echo "Deployed"
Questions: What would you flag here before approving this pipeline? What's missing
to make this deploy safe to run unattended? What would you change first, and why?
Scenario 3 — AI-Assisted Engineering Workflow (20-30 min)
We run development through AI coding agents governed by a set of rules and hooks.
Two engineers report a symptom: the same kind of commit gets blocked by an
automated hook for one of them, but not the other and a project rule about branch
naming contradicts a second rule in a diﬀerent file, so agents apply one or the other
unpredictably.
Questions: How would you diagnose this? What's the underlying failure mode
(not the specific bug, the class of problem)? What would you change in how
these rules/hooks are authored or governed to stop this from recurring?
