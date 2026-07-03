#!/usr/bin/env bash
# ============================================================
#  setup.sh  –  Bootstrap the Locust load-testing environment
#  Usage: bash setup.sh
# ============================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/.venv"

echo "================================================================"
echo "  NMIMS Quiz App – Locust Load Test Setup"
echo "================================================================"

# ── 1. Create virtual environment ──────────────────────────────────
if [ -d "$VENV_DIR" ]; then
    echo "[INFO]  Virtual environment already exists at $VENV_DIR"
else
    echo "[STEP]  Creating Python virtual environment …"
    python3 -m venv "$VENV_DIR"
    echo "[OK]    Virtual environment created."
fi

# ── 2. Activate and install dependencies ───────────────────────────
echo "[STEP]  Installing dependencies …"
# shellcheck source=/dev/null
source "$VENV_DIR/bin/activate"
pip install --quiet --upgrade pip
pip install --quiet -r "$SCRIPT_DIR/requirements.txt"
echo "[OK]    Dependencies installed."

# ── 3. Print next steps ────────────────────────────────────────────
echo ""
echo "================================================================"
echo "  Setup complete! To start testing:"
echo ""
echo "  1.  Activate the virtual environment:"
echo "        source $VENV_DIR/bin/activate"
echo ""
echo "  2.  Edit locustfile.py and set your BASE_URL and credentials"
echo "      (or export them as environment variables):"
echo "        export QUIZ_BASE_URL=http://localhost:8080"
echo "        export STUDENT_EMAIL=student@nmims.edu"
echo "        export STUDENT_PASSWORD=yourpassword"
echo "        export FACULTY_EMAIL=faculty@nmims.edu"
echo "        export FACULTY_PASSWORD=yourpassword"
echo "        export QUIZ_ID=1"
echo ""
echo "  3.  Run Locust (Web UI on http://localhost:8089):"
echo "        locust -f locustfile.py"
echo ""
echo "  4.  Or run headlessly (e.g. 50 users, spawn 5/s, 2 min):"
echo "        locust -f locustfile.py --headless -u 50 -r 5 -t 2m"
echo "================================================================"
