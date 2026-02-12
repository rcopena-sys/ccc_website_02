<div class="modal fade" id="gradeReportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-body p-0">
        
        <div class="grade-report-container">
          <div class="watermark-overlay">
            <?php 
              $name = isset($student['name']) ? strtoupper($student['name']) : '';
              $watermarkText = trim($name) . ' ';
              for ($i = 0; $i < 600; $i++) echo $watermarkText; 
            ?>
          </div>

          <div class="report-content">
            
            <div class="report-header">
              <div class="header-logo">
                <img src="<?php echo BASE_URL; ?>images/ccc_logo.png" alt="CCC Logo">
              </div><br>
              <div class="header-barcode">
                <img src="<?php echo BASE_URL; ?>views/student/generate_barcode.php?code=<?php echo urlencode(isset($student['student_no']) ? $student['student_no'] : ''); ?>" alt="Student Barcode">
              </div>
              <div class="header-text">
                <h1 class="school-name">CITY COLLEGE OF CALAMBA</h1>
                <p class="office-name">OFFICE OF THE COLLEGE REGISTRAR</p>
                <h2 class="report-title">GRADE REPORT</h2>
                <p class="semester"><span id="reportSem"></span><span>, </span><span id="reportSY"></span></p>
              </div>
            </div>

            <div class="student-info-grid">
              <div class="info-row">
                <div class="info-item">Name: <strong><?php echo isset($student['name']) ? htmlspecialchars($student['name']) : ''; ?> </strong></div>
                <div class="info-item">Student No.: <?php echo isset($student['student_no']) ? htmlspecialchars($student['student_no']) : ''; ?></div>
              </div>
              <div class="info-row">
                <div class="info-item">Program: <?php echo isset($student['program']) ? htmlspecialchars($student['program']) : ''; ?></div>
                <div class="info-item">Yr. Level: <?php echo isset($student['year_level']) ? htmlspecialchars($student['year_level']) : ''; ?></div>
              </div>
              <div class="info-row">
                <div class="info-item">Major: <?php echo isset($student['major']) ? htmlspecialchars($student['major']) : ''; ?></div>
                <div class="info-item">Date Generated: <?php echo isset($student['generated']) ? htmlspecialchars($student['generated']) : ''; ?></div>
              </div>
            </div>

            <table class="grades-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Course Code</th>
                  <th>Course Description</th>
                  <th>Units</th>
                  <th>Final Grade</th>
                  <th>Completion</th>
                  <th>Professor</th>
                </tr>
              </thead>
              <tbody id="reportSubjects">
              </tbody>
            </table>

            <div class="bottom-container">
              <div class="stats-column">
                <p>Total no. of units taken: <strong>0</strong></p>
                <p>Total no. of units Passed: <strong id="unitsPassed">0</strong></p>
                <p>Total no. of units Failed: <strong>0</strong></p>
                <p>Total no. of INC units: <strong>0</strong></p>
                <p>Total no. of DRP units: <strong>0</strong></p>
                <p>Academic Status: <span class="status-box">PASSED</span></p>
                <p>GWA: <strong id="gwaValue">N/A</strong></p>
                <p class="no-grade-note">* - No grade yet ** - Not Available</p>
              </div>

              <div class="grading-qr-column">
                <div class="grading-system">
                  <strong>Grading System:</strong><br>
                  1 - 96-100%, 1.25 - 92-95%, 1.5 - 88-91%,<br>
                  1.75 - 84-87%, 2 - 80-83%, 2.25 - 75-79%,<br>
                  2.5 - 70-74%, 2.75 - 65-69%, 3 - 60-64%, 4 -<br>
                  55-59%, FAILED - 0-54%
                </div>
                <div class="qr-code-section">
                   <div class="qr-placeholder">
                     <img id="gradeQr" src="" alt="QR Code">
                     <span>SCAN TO VERIFY</span>
                   </div>
                </div>
              </div>

              <div class="signature-column">
                <div class="verified-by">
                  <p>Verified by:</p>
                  <div class="sig-line"></div>
                  <strong>Nilo O. Armario Jr.</strong>
                </div>
                <div class="registrar-by">
                  <div class="sig-line"></div>
                  <strong>ELVINARD R. REYES</strong><br>
                  <span>College Registrar</span>
                </div>
              </div>
            </div>

            <div class="footer-note">
               <strong>Note:</strong> Not Valid for any transaction unless AUTHENTICATED and SEALED by the Office of College Registrar. Any erasure or alteration made on this copy renders the grade report invalid.
            </div>

            <div class="address-footer">
              Old Municipal Site, Brgy. VII Poblacion, Calamba City, Laguna<br>
              (049) 559-8900 to 8907 / (02) 8-539-5170 to 5171
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
.grade-report-container {
    position: relative;
    background: #fff;
    padding: 40px;
    font-family: Arial, Helvetica, sans-serif; /* Matches the clean sans-serif in the screenshot */
    color: #000;
    overflow: hidden;
    min-height: 800px;
}

/* Watermark CSS */
.watermark-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    font-size: 11px;
    color: rgba(0,0,0,0.04);
    line-height: 1.5;
    letter-spacing: 1px;
    word-break: break-all;
    z-index: 1;
    pointer-events: none;
    text-transform: uppercase;
}

.report-content { position: relative; z-index: 2; }

/* Header Positioning */
.report-header { text-align: center; position: relative; margin-bottom: 20px; }
.header-logo { position: absolute; top: 0; left: 50%; transform: translateX(-50%); }
.header-text { margin-top: 50px; }
.header-logo img { height: 80px; }
.header-barcode { position: absolute; right: 0; top: 0; border-bottom: 2px solid #000; width: 250px; height: 50px; }
.header-barcode img { width: 100%; height: 100%; object-fit: contain; }

.school-name { font-size: 24px; font-weight: bold; margin: 0; }
.office-name { font-size: 14px; margin: 0; letter-spacing: 1px; }
.report-title { font-size: 18px; font-weight: bold; margin-top: 10px; }
.semester { font-weight: bold; font-size: 14px; }

/* Student Info Grid */
.student-info-grid { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 10px 0; margin-bottom: 5px; }
.info-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
.info-item { flex: 1; font-size: 13px; }

/* Table Styling */
.grades-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.grades-table th { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 5px; text-align: left; }
.grades-table td { padding: 4px 5px; }
.nothing-follows { text-align: center; font-style: italic; font-weight: bold; }

/* Layout for bottom columns */
.bottom-container { display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 20px; margin-top: 15px; font-size: 12px; }
.status-box { font-weight: bold; padding: 2px 5px; }

.grading-system { font-size: 11px; line-height: 1.4; margin-bottom: 10px; }
.qr-placeholder { text-align: center; font-size: 10px; font-weight: bold; }
.qr-placeholder img { width: 80px; display: block; margin: 0 auto; }

.signature-column { text-align: center; display: flex; flex-direction: column; justify-content: space-between; }
.sig-line { border-top: 1px solid #000; width: 100%; margin-top: 40px; margin-bottom: 5px; }

.footer-note { font-size: 10px; font-style: italic; margin-top: 20px; line-height: 1.2; }
.address-footer { text-align: center; font-size: 11px; margin-top: 20px; border-top: 1px solid #ccc; padding-top: 5px; }
</style>