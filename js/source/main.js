/*
 * ==========================================================
 * PHP UI MAIN SCRIPT
 * ==========================================================
 *
 */

"use strict";

// PROCESADOR DE MENSAJES SIMBOLOS
function processMessage(message) {
  return (
    message
      .replace(/- /g, "• ")
      .replace(/[\[\]"\,}]/g, "")
      .replace(/@s\.whatsapp\.net/g, "")
      .replace('["', "<div class='group-chat-reply'>")
      .replace('"]', "</div>")
      .replace("→Forwarded←", '<i class="bi bi-arrow-right-square"></i> ')
      .replace(/\{agent_name\}/g, '<i class="bi-people-fill"></i>')
      .replace(
        /\[buttons\s+options="([^"]+)"\s+message="([^"]+)"\]/g,
        '<i class="bi-file-text" data-options="$1" data-message="$2"></i>'
      )
      .replace(/\[card[^\]]*\]/g, '<i class="bi-file-text"></i>')
      .replace(/〚/g, " ")
      .replace(/〛/g, " ")
      // Replace single underscores for italic
      .replace(/(_[^_]+_)/g, function (match) {
        return `<em>${match.slice(1, -1)}</em>`;
      })
      // Replace single backticks for code
      .replace(/(`[^`]+`)/g, function (match) {
        return `<code>${match.slice(1, -1)}</code>`;
      })
      // Replace single tildes for strikethrough
      .replace(/~([^~]+)~/g, function (match) {
        return `<del>${match.slice(1, -1)}</del>`;
      })
      // Replace single asterisks for bold
      .replace(/(\*[^*]+\*)/g, function (match) {
        return `<strong>${match.slice(1, -1)}</strong>`;
      })
      // Custom replacement for ⟦ ⟧
      .replace(/⟦(.*?)⟧/g, "\u200E⟦$1⟧\u200E")
  );
}

(function ($) {
  var version = "2.28";
  var main;
  var global;
  var upload_target;
  var admin = false;
  var tickets = false;
  var timeout = false;
  var timeout_label_date = [false, false];
  var interval = false;
  var timeout_debounce = [];
  var previous_search;
  var sb_current_user = false;
  var chat;
  var chat_editor;
  var chat_textarea;
  var chat_header;
  var chat_status;
  var chat_emoji;
  var chat_scroll_area;
  var label_date;
  var label_date_items = false;
  var label_date_show = false;
  var label_date_history = [9999999, ""];
  var document_title = document.title;
  var CHAT_SETTINGS = {};
  var ADMIN_SETTINGS;
  var mobile = $(window).width() < 555;
  var today = new Date();
  var bot_id;
  var force_action = "";
  var dialogflow_human_takeover;
  var agents_online = false;
  var ND = "undefined";
  var cookies_supported = true;
  var utc_offset = new Date().getTimezoneOffset() * 60000;
  var cloud_data = false;
  // var articles_page = false;
  var load_more = 30;
  var total_more;

  /*
   * ----------------------------------------------------------
   * EXTERNAL PLUGINS
   * ----------------------------------------------------------
   */

  // Auto Expand Scroll Area |
  $.fn.extend({
    manualExpandTextarea: function () {
      var t = this[0];
      (t.style.height = "auto"), (t.style.maxHeight = "25px");
      window.getComputedStyle(t);
      (t.style.height = (t.scrollHeight > 35 ? 60 : t.scrollHeight) + "px"),
        (t.style.maxHeight = ""),
        $(t).trigger("textareaChanged");
    },
    autoExpandTextarea: function () {
      var t = this[0];
      t.addEventListener(
        "input",
        function (e) {
          $(t).manualExpandTextarea();
        },
        !1
      );
    },
  });

  // autolink-js
  // 	(function() {
  // 		var t = [].slice;
  // 		String.prototype.autoLink = function() {
  // 			var n, a, r, i, c, e, l;
  // 			return e = /(^|[\s\n]|<[A-Za-z]*\/?>)((?:https?|ftp):\/\/[\-A-Z0-9+\u0026\u2019@#\/%?=()~_|!:,.;]*[\-A-Z0-9+\u0026@#\/%=~()_|])/gi, 0 < (c = 1 <= arguments.length ? t.call(arguments, 0) : []).length ? (i = c[0], n = i.callback, r = function() {
  // 				var t;
  // 				for (a in t = [], i) l = i[a], "callback" !== a && t.push(" " + a + "='" + l + "'");
  // 				return t
  // 			}().join(""), this.replace(e, function(t, a, i) {
  // 				return "" + a + (("function" == typeof n ? n(i) : void 0) || "<a href='" + i + "'" + r + ">" + i + "</a>")
  // 			})) : this.replace(e, "$1<a href='$2'>$2</a>")
  // 		}
  // 	}).call(this);

  (function () {
    // Define the autoLink function as a prototype of the String object
    String.prototype.autoLink = function () {
      // Initialize variables
      var match, // Temporary variable to store matches
        options, // Options for customizing link behavior
        linkAttributes, // Attributes for the generated link
        regex; // Regular expression for matching URLs

      // Regular expression for matching URLs in the text
      regex =
        /(^|[\s\n]|<[A-Za-z]*\/?>)((?:https?|ftp):\/\/[\-A-Z0-9+\u0026\u2019@#\/%?=()~_|!:,.;]*[\-A-Z0-9+\u0026@#\/%=~()_|]+)/gi;

      // Check if custom options are provided
      options = arguments.length > 0 ? [].slice.call(arguments, 0)[0] : {};

      // Extract link attributes from options, if provided
      linkAttributes = (function () {
        var attributes = [];
        for (var key in options) {
          if (key !== "callback") {
            attributes.push(" " + key + "='" + options[key] + "'");
          }
        }
        return attributes.join("");
      })();

      // Replace URLs in the text with clickable links
      return this.replace(regex, function (match, prefix, url) {
        // If the URL matches a Google Maps URL
        if (url.match(/maps.google.com/g)) {
          // Extract latitude and longitude from the URL
          var matches = url.match(/q=([\d.-]+),([\d.-]+)/);
          if (matches) {
            var latitude = matches[1];
            var longitude = matches[2];
            // Generate an iframe for embedding the map
            return (
              "<iframe width='270' height='270' frameborder='0'  style='margin:0px -21px -4px -7px; border-radius:4px; border: 0; width: auto;' src='https://maps.google.com/maps?q=" +
              latitude +
              "," +
              longitude +
              "&amp;output=embed'></iframe>"
            );
          } else {
            // If latitude and longitude are not found, use default values
            return "<iframe width='270' height='270' frameborder='0' style='border:0;' src='https://maps.google.com/maps?q=-00.000000,-00.000000&amp;output=embed'></iframe>";
          }
        } else {
          // If the URL is not a Google Maps URL, create a clickable link
          // Call the custom callback function if provided, otherwise create a standard link
          return (
            prefix + // Add the prefix (space, newline, or HTML tag) before the link
            (typeof options.callback === "function"
              ? options.callback(url)
              : "") + // Custom callback function result or empty string
            "<a href='" +
            url +
            "'" +
            linkAttributes +
            ">" +
            url +
            "</a>" + // Clickable link
            (typeof options.callback === "function" ? "" : " ") // Add space after the link if no custom callback function is provided
          );
        }
      });
    };
  }).call(this);

  /*
   * ----------------------------------------------------------
   * # FUNCTIONS
   * ----------------------------------------------------------
   */

  var SBF = {
    // Main Ajax function

    ajax: function (data, onSuccess = false) {
      data["login-cookie"] = this.loginCookie();
      if (!("user_id" in data) && activeUser()) {
        data["user_id"] = activeUser().id;
      }
      if (!("language" in data) && typeof SB_LANG != ND) {
        data["language"] = SB_LANG;
      }
      if (cloud_data) {
        data["cloud"] = cloud_data;
      }
      $.ajax({
        method: "POST",
        url: SB_AJAX_URL,
        data: data,
      }).done((response) => {
        let result;
        if (Array.isArray(response)) {
          result = response;
        } else {
          try {
            result = JSON.parse(response);
          } catch (e) {
            SBChat.is_busy_update = false;
            SBChat.busy(false);
            SBF.error(
              response.length > 500
                ? response.substr(0, 500) +
                    "... Check the console for more details."
                : response,
              `SBF.ajax.${data["function"]}`
            );
            return;
          }
        }
        if (result[0] == "success") {
          if (onSuccess) onSuccess(result[1]);
        } else if (SBF.errorValidation(result)) {
          if (onSuccess) onSuccess(result);
        } else {
          if (admin && result[1] == "security-error") {
            setTimeout(() => {
              SBF.reset();
            }, 1000);
          }
          SBChat.is_busy_update = false;
          SBChat.busy(false);
          SBF.error(
            result[1] +
              (SBF.null(result[2]) ? "" : "\nFunction name: " + result[2]) +
              (SBF.null(result[3])
                ? ""
                : "\nError message: " +
                  (typeof result[3] == "string"
                    ? result[3]
                    : "error" in result[3] && "message" in result[3]["error"]
                    ? `${result[3]["error"]["message"]} in function '${result[3]["error"]["function"]}'`
                    : result[3])),
            `SBF.ajax.${data["function"]}`
          );
        }
      });
    },

    // Cors function
    cors: function (method = "GET", url, onSuccess) {
      let xhr = new XMLHttpRequest();
      if ("withCredentials" in xhr) {
        xhr.open(method, url, true);
      } else if (typeof XDomainRequest != ND) {
        xhr = new XDomainRequest();
        xhr.open(method, url);
      } else {
        return false;
      }
      xhr.onload = function () {
        onSuccess(xhr.responseText);
      };
      xhr.onerror = function () {
        return false;
      };
      xhr.send();
    },
    // Uploads
    upload: function (form, onSuccess) {
      if (cloud_data) form.append("cloud", cloud_data); //added from old

      jQuery.ajax({
        url: STMBX_URL + "/include/upload.php",
        cache: false,
        contentType: false,
        processData: false,
        data: form,
        type: "POST",
        success: function (response) {
          onSuccess(response);
        },
      });
    },
    //link hostname get
    getHostname: function (url) {
      return new URL(url).hostname.replace("www.", "");
    },
    //text limit set
    limitText: function (text, limit) {
      let setimit = text != null ? text.slice(0, limit) : "";
      return setimit.length > limit - 1 ? setimit + "..." : setimit;
    },

    // UTC Time
    UTC: function (datetime) {
      return new Date(datetime).getTime() - utc_offset;
    },

    // Check if a variable is null or empty
    null: function (obj) {
      if (
        typeof obj !== ND &&
        obj !== null &&
        obj !== "null" &&
        obj !== false &&
        (obj.length > 0 || typeof obj == "number" || typeof obj.length == ND) &&
        obj !== ND
      )
        return false;
      else return true;
    },

    // Deactivate and hide the elements
    deactivateAll: function () {
      global
        .find(".sb-popup, .sb-tooltip, .sb-list .sb-menu, .sb-select ul")
        .sbActive(false);
    },

    // Deselect the content of the target
    deselectAll: function () {
      if (window.getSelection) {
        window.getSelection().removeAllRanges();
      } else if (document.selection) {
        document.selection.empty();
      }
    },

    // Get URL parameters
    getURL: function (name = false, url = false) {
      if (url == false) {
        url = location.search;
      }
      if (name == false) {
        var c = url.split("?").pop().split("&");
        var p = {};
        for (var i = 0; i < c.length; i++) {
          var d = c[i].split("=");
          p[d[0]] = SBF.escape(d[1]);
        }
        return p;
      }
      if (url.indexOf("?") > 0) {
        url = url.substr(0, url.indexOf("?"));
      }
      return SBF.escape(
        decodeURIComponent(
          (new RegExp("[?|&]" + name + "=" + "([^&;]+?)(&|#|;|$)").exec(
            url
          ) || [, ""])[1].replace(/\+/g, "%20") || ""
        )
      );
    },

    URL: function () {
      return window.location.href.substr(0, window.location.href.indexOf("?"));
    },

    // Convert a string to slug and inverse
    stringToSlug: function (string) {
      string = string.trim();
      string = string.toLowerCase();
      var from = "åàáãäâèéëêìíïîòóöôùúüûñç·/_,:;";
      var to = "aaaaaaeeeeiiiioooouuuunc------";
      for (var i = 0, l = from.length; i < l; i++) {
        string = string.replace(new RegExp(from.charAt(i), "g"), to.charAt(i));
      }
      return string
        .replace(/[^a-z0-9 -]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-+/, "")
        .replace(/-+$/, "")
        .replace(/ /g, "");
    },

    slugToString: function (string) {
      string = string.replace(/_/g, " ").replace(/-/g, " ");
      return string.charAt(0).toUpperCase() + string.slice(1);
    },

    // Random string
    random: function () {
      let chars =
        "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
      let result = "";
      for (var i = 5; i > 0; --i)
        result += chars[Math.floor(Math.random() * 62)];
      return result;
    },

    // Check if a user type is an agent
    isAgent: function (user_type) {
      return user_type == "agent" || user_type == "admin" || user_type == "bot";
    },

    // Get cors elapsed of a given date from now
    beautifyTime: function (datetime, extended = false, future = false) {
      let date;
      if (datetime == "0000-00-00 00:00:00") return "";
      if (datetime.indexOf("-") > 0) {
        let arr = datetime.split(/[- :]/);
        date = new Date(arr[0], arr[1] - 1, arr[2], arr[3], arr[4], arr[5]);
      } else {
        let arr = datetime.split(/[. :]/);
        date = new Date(arr[2], arr[1] - 1, arr[0], arr[3], arr[4], arr[5]);
      }
      let date_string = new Date(
        Date.UTC(
          date.getFullYear(),
          date.getMonth(),
          date.getDate(),
          date.getHours(),
          date.getMinutes(),
          date.getSeconds()
        )
      );
      let diff_days =
        Math.round((new Date() - date_string) / (1000 * 60 * 60 * 24)) *
        (future ? -1 : 1);
      let days = [
        sb_("Sunday"),
        sb_("Monday"),
        sb_("Tuesday"),
        sb_("Wednesday"),
        sb_("Thursday"),
        sb_("Friday"),
        sb_("Saturday"),
      ];
      let time = date_string.toLocaleTimeString(navigator.language, {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false, // Force 24-hour format
      });
      if (diff_days < 1) {
        return extended
          ? `<span>${sb_("Today")}</span> <span>${time}</span>`
          : `<span style="text-transform: uppercase;" data-today>${time}</span>`;
      } else if (diff_days < 3) {
        return `<span>${days[date_string.getDay()]}</span>${
          extended ? ` <span>${time}</span>` : ""
        }`;
      } else {
        return `<span>${date_string.toLocaleDateString()}</span>${
          extended ? ` <span>${time}</span>` : ""
        }`;
      }
    },

    // Get the unix timestamp value of a data string with format yyyy-mm-dd hh:mm:ss
    unix: function (datetime) {
      let arr = datetime.split(/[- :]/);
      return Date.UTC(arr[0], arr[1] - 1, arr[2], arr[3], arr[4], arr[5]);
    },

    // Generate a string containing the agent location and time
    getLocationTimeString: function (details, onSuccess) {
      if ("timezone" in details) {
        let location = {};
        location["timezone"] = details["timezone"]["value"];
        location["country"] =
          "country" in details
            ? details["country"]["value"]
            : location["timezone"].split("/")[0].replace(/_/g, " ");
        location["city"] =
          "city" in details
            ? details["city"]["value"]
            : location["timezone"].split("/")[1].replace(/_/g, " ");
        SBF.cors(
          "GET",
          "https://worldtimeapi.org/api/timezone/" + location["timezone"],
          function (response) {
            response = JSON.parse(response);
            if (SBF.null(response) || !SBF.null(response["error"])) {
              SBF.error(response, "SBF.getLocationTimeString()");
              onSuccess(responseonSuccess);
            } else {
              let datetime = response["datetime"].replace("T", " ");
              onSuccess(
                `${new Date(
                  datetime.substr(0, datetime.indexOf("."))
                ).toLocaleString([], {
                  hour: "2-digit",
                  minute: "2-digit",
                })} ${sb_("in")} ${location["city"] ? location["city"] : ""}${
                  location["country"] ? ", " + location["country"] : ""
                }`
              );
            }
          }
        );
      }
    },

    // Date string
    dateDB: function (date) {
      if (date == "now") {
        date = new Date().toISOString().replace("T", " ");
        if (date.indexOf(".") > 0) {
          date = date.substr(0, date.indexOf("."));
        }
        return date;
      } else {
        return `${date.getFullYear()}-${
          date.getMonth() + 1
        }-${date.getDate()} ${date.getHours()}:${date.getMinutes()}:${date.getSeconds()}`;
      }
    },

    localTime: function (utc, zone) {
      return utc != false
        ? SBF.getTimezoneOffset(zone) == new Date().getTimezoneOffset()
          ? utc
          : moment(utc).format("MM/DD/YYYY hh:mm A")
        : "";
    },
    getTimezoneOffset: function (timezone) {
      let dt = new Date();
      let zonetime = dt.toLocaleString("en-US", {
        timeZone: timezone,
      });
      let adjDate = new Date(zonetime);
      let noSecs = new Date(dt.getTime());
      let diff = Math.round((adjDate.getTime() - noSecs.getTime()) / 60000);
      return dt.getTimezoneOffset() - diff;
    },

    //Set and get users last activity
    updateUsersActivity: function (user_id, return_user_id, onSuccess) {
      if (SBPusher.active) {
        onSuccess(
          SBPusher.online_ids.includes(return_user_id) ? "online" : "offline"
        );
      } else {
        SBF.ajax(
          {
            function: "update-users-last-activity",
            user_id: user_id,
            return_user_id: return_user_id,
            // check_slack: !admin && CHAT_SETTINGS["slack-active"],
          },
          (response) => {
            if (response === "online") {
              onSuccess("online");
            } else {
              onSuccess("offline");
            }
          }
        );
      }
    },

    // Search functions
    search: function (search, searchFunction) {
      search = search.toLowerCase();
      if (search == previous_search) {
        global.find(".sb-search-btn i").sbLoading(false);
        return;
      }
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        previous_search = search;
        searchFunction();
      }, 200);
    },

    searchClear: function (icon, onSuccess) {
      let search = $(icon).next().val();
      if (search) {
        $(icon).next().val("");
        onSuccess();
      }
    },

    // Routin.bot error js reporting
    error: function (message, function_name) {
      if (admin && SBAdmin.is_logout) return;
      if (message instanceof Error) message = message.message;
      if (message[message.length - 1] == ".") message = message.slice(0, -1);
      if (message.includes("Routin.bot error"))
        message = message.replace("Routin.bot error ", "").replace("]:", "]");
      if (
        admin &&
        message &&
        !function_name.includes("update-users-last-activity") &&
        !function_name.startsWith("security-error")
      )
        SBAdmin.dialog(
          `[Error] [${function_name}] ${message}. Check the console for more details.`,
          "info"
        );
      global.find(".sb-loading").sbLoading(false);
      SBF.event("SBError", {
        message: message,
        function_name: function_name,
      });
      throw new Error(`Routin.bot error [${function_name}]: ${message}.`);
    },

    errorValidation: function (response, code = true) {
      return (
        Array.isArray(response) &&
        response[0] === "validation-error" &&
        (code === true || response[1] == code)
      );
    },

    // Login
    loginForm: function (button, area = false, onSuccess = false) {
      button = $(button);
      if (!button.sbLoading()) {
        if (area === false) area = button.closest(".sb-rich-login");
        else area = $(area);
        let email = $.trim(area.find("#email input").val());
        let password = $.trim(area.find("#password input").val());
        if (email == "" || password == "") {
          area
            .find(".sb-info")
            .html(sb_("Please insert email and password."))
            .sbActive(true);
        } else {
          SBF.ajax(
            {
              function: "login",
              email: email,
              password: password,
            },
            (response) => {
              if (response && Array.isArray(response)) {
                if (!admin && this.isAgent(response[0]["user_type"])) {
                  SBForm.showErrorMessage(
                    area,
                    "You cannot sign in as an agent."
                  );
                  SBChat.scrollBottom();
                } else {
                  let user = new SBUser(response[0]);
                  user.set(
                    "conversation_id",
                    SBChat.conversation ? SBChat.conversation.id : false
                  );
                  this.loginCookie(response[1]);
                  this.event("SBLoginForm", user);
                  if (onSuccess) onSuccess(response);
                  // readText("");
                }
              } else {
                area
                  .find(".sb-info")
                  .html(sb_("Invalid email or password."))
                  .sbActive(true);

                if (!admin) SBChat.scrollBottom();
              }

              button.sbLoading(false);
            }
          );
          area.find(".sb-info").html("").sbActive(false);
          button.sbLoading(true);
        }
      }
    },

    // Set the login cookie
    loginCookie: function (value = false) {
      if (value === false)
        return this.cookie("sb-login")
          ? this.cookie("sb-login")
          : storage("login");
      if (CHAT_SETTINGS.cloud) {
        storage("login", value);
      } else {
        this.cookie("sb-login", value, 2, "set"); // xxx xxx
      }
    },

    // Login
    login: function (
      email = "",
      password = "",
      user_id = "",
      token = "",
      onSuccess = false
    ) {
      SBF.ajax(
        {
          function: "login",
          email: email,
          password: password,
          user_id: user_id,
          token: token,
        },
        (response) => {
          if (response != false && Array.isArray(response)) {
            this.loginCookie(response[1]);
            if (onSuccess) onSuccess(response);
            return true;
          } else {
            return false;
          }
        }
      );
    },

    // Logout
    logout: function (reload = true) {
      SBChat.stopRealTime();
      this.cookie("sb-login", "", "", false);
      storage("open-conversation", "");
      storage("login", "");
      SBChat.conversations = false;
      activeUser(false);
      if (typeof sb_beams_client !== ND) {
        sb_beams_client.stop();
      }
      SBF.ajax(
        {
          function: "logout",
        },
        () => {
          SBF.event("SBLogout");
          if (reload) {
            setTimeout(() => {
              location.reload();
            }, 500);
          }
        }
      );
    },

    // Return the active user
    activeUser: function () {
      return activeUser();
    },
    //reply message replace
    getReply: function (message) {
      const pattern = new RegExp("^([{,+,0-9,}]+[@s.whatsapp.net])");
      const reply = pattern.test(message);
      return reply;
    },
    // Get the active user
    getActiveUser: function (database = false, onSuccess) {
      let app_login = SBApps.login();
      if (
        !app_login &&
        (storage("wp-login") ||
          storage("whmcs-login") ||
          storage("perfex-login") ||
          storage("aecommerce-login"))
      ) {
        this.cookie("sb-login", "", "", "delete");
        activeUser(false);
        storage("login", "");
      }
      SBF.ajax(
        {
          function: "get-active-user",
          db: database,
          login_app: JSON.stringify(app_login),
          user_token: SBF.getURL("token"),
        },
        (response) => {
          if (!response) {
            onSuccess();
            return false;
          } else {
            if ("cookie" in response) SBF.loginCookie(response["cookie"]);
            if ("user_type" in response) {
              if (!admin && SBF.isAgent(response["user_type"])) {
                let message =
                  "Cierra sesión o utiliza otro navegador para  ver el botón de chat o iniciar sesión como usuario registrado. Puedes deslogearte escribiendo SBF.reset() en la consola del navegador.";
                if (!storage("double-login-alert")) {
                  storage("double-login-alert", true);
                  alert(message);
                }
                console.warn("Routin.bot: " + message);
                SBF.event("SBDoubleLoginError");
              } else {
                activeUser(
                  new SBUser(
                    response,
                    "phone" in response
                      ? {
                          phone: response.phone,
                        }
                      : {}
                  )
                );
                SBPusher.start();
                if (app_login) {
                  storage(app_login[1] + "-login", true);
                }
                onSuccess();
                SBF.event("SBActiveUserLoaded", response);
              }
            }
          }
        }
      );
    },

    // Clean
    reset: function () {
      // let cookies = ["sb-login", "sb-dialogflow-disabled"];
      let cookies = ["sb-login", "sb-cloud", "sb-dialogflow-disabled"];

      for (var i = 0; i < cookies.length; i++) {
        this.cookie(cookies[i], "", 0, false);
      }
      try {
        localStorage.removeItem("routin-storage");
      } catch (e) {}
      this.logout();
    },

    // Lightbox
    lightbox: function (content) {
      let lightbox = $(admin ? global : main).find(".sb-lightbox-media");
      lightbox.sbActive(true).find(" > div").html(content);
      if (admin) SBAdmin.open_popup = lightbox;
    },

    // Manage the local storage
    storage: function (key, value = ND) {
      try {
        if (typeof localStorage == ND) return false;
      } catch (e) {
        return false;
      }
      let settings = localStorage.getItem("routin-storage");
      if (settings === null) {
        settings = {};
      } else {
        settings = JSON.parse(settings);
      }
      if (value === ND) {
        return key in settings ? settings[key] : false;
      } else {
        if (!value) {
          delete settings[key];
        } else {
          settings[key] = value;
        }
        localStorage.setItem("routin-storage", JSON.stringify(settings));
      }
    },

    // Save the current time or check if the saved time is older than now plus the given hours
    storageTime: function (key, hours = false) {
      if (hours === false) {
        storage(key, today.getTime());
      } else {
        return (
          storage(key) == false ||
          today.getTime() - storage(key) > 3600000 * hours
        );
      }
    },

    // Set or get a cookie
    cookie: function (
      name,
      value = false,
      expiration_days = false,
      action = "get",
      seconds = false
    ) {
      let cookie_https =
        location.protocol == "https:" ? "SameSite=None;Secure;" : "";
      let settings = window[admin ? "SB_ADMIN_SETTINGS" : "CHAT_SETTINGS"];
      let domain =
        settings && settings["cookie-domain"]
          ? "domain=" + settings["cookie-domain"] + ";"
          : "";
      if (action == "get") {
        if (!cookies_supported) {
          return this.storage(name);
        }
        let cookies = document.cookie.split(";");
        for (var i = 0; i < cookies.length; i++) {
          var cookie = cookies[i];
          while (cookie.charAt(0) == " ") {
            cookie = cookie.substring(1);
          }
          if (cookie.indexOf(name) == 0) {
            let value = cookie.substring(name.length + 1, cookie.length);
            return this.null(value) ? false : value;
          }
        }
        return false;
      } else if (action == "set") {
        if (!cookies_supported) {
          this.storage(name, value);
        } else {
          let date = new Date();
          date.setTime(
            date.getTime() + expiration_days * (seconds ? 1 : 86400) * 1000
          );
          document.cookie =
            name +
            "=" +
            value +
            ";expires=" +
            date.toUTCString() +
            ";path=/;" +
            cookie_https +
            domain;
        }
      } else if (this.cookie(name)) {
        if (!cookies_supported) {
          this.storage(name, "");
        } else {
          document.cookie =
            name +
            "=" +
            value +
            ";expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;" +
            cookie_https +
            domain;
        }
      }
    },

    // Return a front setting
    setting: function (key) {
      return typeof CHAT_SETTINGS !== ND && key in CHAT_SETTINGS
        ? CHAT_SETTINGS[key]
        : false;
    },
    get_select_setting: function () {
      const getsetting = [
        {
          name: "rate-and-review",
          id: "rate-review",
          type: "one",
        },
        {
          name: "label-names",
          id: "label-names",
          type: "one",
        },
        {
          name: "whatsapp-cloud",
          id: "cloud-active",
          type: "one",
        },
      ];
      if (chat_editor) {
        SBF.ajax(
          {
            function: "get-select-setting",
            setting: getsetting,
          },
          (response) => {
            ADMIN_SETTINGS = response;
            let test = SBF.admin_set("rate-and-review");
          }
        );
      }
    },

    //Return a admin setting
    // 		admin_set: function(key = false) {
    // 			return key ? ((Object.keys( ADMIN_SETTINGS).length!==0) && ADMIN_SETTINGS.hasOwnProperty(key) ? ADMIN_SETTINGS[key] : false) : ADMIN_SETTINGS;
    // 		},

    //Return a admin setting (edited)
    admin_set: function (key = false) {
      if (!ADMIN_SETTINGS) return false; // add this line to check if ADMIN_SETTINGS is defined
      return key
        ? Object.keys(ADMIN_SETTINGS).length !== 0 &&
          ADMIN_SETTINGS.hasOwnProperty(key)
          ? ADMIN_SETTINGS[key]
          : false
        : ADMIN_SETTINGS;
    },

    //Return a object setting value
    get_value: function (value) {
      return value instanceof Array && value.length > 0 ? value[0] : value;
    },
    // Return the shortcode array
    shortcode: function (shortcode) {
      return SBRichMessages.shortcode(shortcode);
    },

    // Events and webhooks
    event: function (name, parameters) {
      $(document).trigger(name, parameters);
      let webhooks = admin
        ? typeof SB_ADMIN_SETTINGS === ND
          ? false
          : SB_ADMIN_SETTINGS["webhooks"]
        : CHAT_SETTINGS["webhooks"];
      let webhooks_list = {
        SBSMSSent: "sms-sent",
        SBLoginForm: "login",
        SBRegistrationForm: "registration",
        SBUserDeleted: "user-deleted",
        SBEmailSent: "email-sent",
        SBNewMessagesReceived: "new-message",
        SBNewConversationReceived: "new-conversation",
        SBActiveConversationStatusUpdated: "conversation-status-updated",
        // SBSlackMessageSent: "slack-message-sent",
        SBMessageDeleted: "message-deleted",
        SBRichMessageSubmit: "rich-message",
        SBNewEmailAddress: "new-email-address",
      };
      if (webhooks && name in webhooks_list) {
        if (webhooks !== true) {
          if (!Array.isArray(webhooks))
            webhooks = webhooks.replace(/ /g, "").split(",");
          if (!webhooks.includes(webhooks_list[name])) return;
        }
        SBF.ajax({
          function: "webhooks",
          function_name: name,
          parameters: parameters,
        });
      }
    },

    // Translate a string
    translate: function (string) {
      if (
        (!admin && SBF.null(CHAT_SETTINGS)) ||
        (admin && typeof SB_TRANSLATIONS === ND)
      )
        return string;
      let translations = admin
        ? SB_TRANSLATIONS
        : CHAT_SETTINGS["translations"];
      if (translations && string in translations) {
        return translations[string] == "" ? string : translations[string];
      } else {
        return string;
      }
    },

    // Escape a string
    escape: function (string) {
      if (!string) return string;
      return string
        .replaceAll("<script", "&lt;script")
        .replaceAll("</script", "&lt;/script")
        .replaceAll("javascript:", "")
        .replaceAll("onclick=", "")
        .replaceAll("onerror=", "");
    },

    // Visibility change function
    visibilityChange: function (visibility = "") {
      if (visibility == "hidden") {
        if (!admin) {
          SBChat.stopRealTime();
        }
        SBChat.tab_active = false;
      } else {
        if (activeUser() && !admin) {
          SBChat.startRealTime();
        }
        SBChat.tab_active = true;
        clearInterval(interval);
        document.title = document_title;
      }
    },

    // Convert a settings string to an Array
    settingsStringToArray: function (string) {
      if (this.null(string)) return [];
      let result = [];
      string = string.split(",");
      for (var i = 0; i < string.length; i++) {
        let values = string[i].split(":");
        result[values[0]] =
          values[1] == "false" ? false : values[1] == "true" ? true : values[1];
      }
      return result;
    },

    // Open a browser window
    openWindow: function (link, width = 550, height = 350) {
      let left = screen.width / 2 - width / 2;
      let top = screen.height / 2 - height / 2;
      window.open(
        link,
        "targetWindow",
        "toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=" +
          width +
          ",height=" +
          height +
          ", top=" +
          top +
          ", left=" +
          left
      );
      return false;
    },

    // Convert a date to local time
    convertUTCDateToLocalDate: function (date, UTCoffset = 0) {
      date = new Date(date); // Y/m/d H:i:s
      date = new Date(date.getTime() + UTCoffset * 3600000);
      return new Date(date.getTime() + utc_offset * -1);
    },

    // Load a js or css file
    loadResource: function (url, script = false) {
      let head = document.getElementsByTagName("head")[0];
      let resource = document.createElement(script ? "script" : "link");
      if (script) {
        resource.src = url + "?v=" + version;
      } else {
        resource.id = "routin";
        resource.rel = "stylesheet";
        resource.type = "text/css";
        resource.href = url + "?v=" + version;
        resource.media = "all";
      }
      head.appendChild(resource);
    },

    uploadResponse: function (response) {
      response = JSON.parse(response);
      if (response[0] == "success") {
        if (response[1] == "extension_error") {
          let message =
            "The file you are trying to upload has an extension that is not allowed.";
          if (admin) SBAdmin.dialog(message, "info");
          else alert(message);
        } else if ($(upload_target).hasClass("sb-input-image")) {
          $(upload_target)
            .find(".image")
            .attr("data-value", "")
            .css("background-image", "");
          setTimeout(() => {
            $(upload_target)
              .find(".image")
              .attr("data-value", response[1])
              .css(
                "background-image",
                `url("${response[1]}?v=${SBF.random()}")`
              )
              .append('<i class="bi-x-lg"></i>');
            upload_target = false;
          }, 500);
        } else {
          //   let name = response[1].substr(response[1].lastIndexOf("/") + 1);
          //   chat_editor
          //     .find(".sb-attachments")
          //     .append(
          //       `<div data-name="${name}" data-value="${response[1]}" data-id="${response[2]}"><i class="bi-x-lg"></i>${name}</div>`
          //     );
          //   SBChat.activateBar();
          // }

          let name = response[1].substr(response[1].lastIndexOf("/") + 1);
          let attachmentElement = "";

          // Check if response[1] is a valid image URL
          const isImageURL = /\.(jpg|jpeg|png|gif|bmp|)$/.test(
            response[1].toLowerCase()
          );

          // Check if response[1] is a valid audio URL
          const isAudioURL = /\.(mp3|ogg)$/.test(response[1].toLowerCase());

          // Check if response[1] is a valid Office format URL
          const isOfficeURL = /\.(docx|pptx|xlsx|doc|ppt|xls)$/.test(
            response[1].toLowerCase()
          );

          // Conditionally construct HTML
          if (isImageURL) {
            attachmentElement = `<div data-name="${name}" data-value="${response[1]}" data-id="${response[2]}">
                                    <img style="border-radius: .4rem; width: 33px; vertical-align: middle;object-fit:cover;" src="${response[1]}" width="30" height="30">
                                     ${name}<i class="bi-x-lg"></i>
                                  </div>`;
          } else if (isAudioURL) {
            attachmentElement = `<div style="display:flex;flex-direction:row;align-items: center;" data-name="${name}" data-value="${
              response[1]
            }" data-id="${response[2]}">
                                    <audio controls style="max-width: 100%; max-height: 33px; border-radius: var(--chat-rounded-size-6);">
                                      <source src="${
                                        response[1]
                                      }" type="audio/${response[1]
              .split(".")
              .pop()}">
                                      Your browser does not support the audio element.
                                    </audio>
                                    <i class="bi-x-lg"></i>
                                  </div>`;
          } else if (isOfficeURL) {
            // For Office formats, use doc.png as a placeholder image
            attachmentElement = `<div data-name="${name}" data-value="${response[1]}" data-id="${response[2]}">
                                    <i class="bi bi-file-earmark-text-fill" style="font-size:1.89rem; color: var(--chat-text-primary)"></i>
                                     <i class="bi-x-lg"></i>
                                  </div>`;
          } else {
            attachmentElement = `<div data-name="${name}" data-value="${response[1]}" data-id="${response[2]}">
                                     ${name}<i class="bi-x-lg"></i>
                                  </div>`;
          }

          chat_editor.find(".sb-attachments").append(attachmentElement);
          SBChat.activateBar();
        }
      } else {
        SBF.error(response[1], "sb-upload-files.change");
      }
      this.busy = function (status) {
        // code to set busy status of the current object
      };
    },
    // Debounce
    debounce: function (bounceFunction, id, interval = 500) {
      if (!(id in timeout_debounce)) {
        timeout_debounce[id] = true;
        bounceFunction();
        setTimeout(() => {
          delete timeout_debounce[id];
        }, interval);
      }
    },
  };

  /*
   * ----------------------------------------------------------
   * # PUSHER
   * ----------------------------------------------------------
   */

  var SBPusher = {
    channels: {},
    channels_presence: [],
    active: false,
    pusher: false,
    started: false,
    pusher_beams: false,
    initialized: false,
    online_ids: [],
    init_push_notifications: false,
    sw: false,

    // Initialize Pusher
    init: function (onSuccess = false) {
      if (SBPusher.active) {
        if (this.pusher) {
          return onSuccess ? onSuccess() : true;
        } else if (onSuccess) {
          $(window).one("SBPusherInit", () => {
            onSuccess();
          });
        } else return;
        this.initialized = true;
        $.getScript("https://js.pusher.com/7.0/pusher.min.js", () => {
          this.pusher = new Pusher(
            admin
              ? SB_ADMIN_SETTINGS["pusher-key"]
              : CHAT_SETTINGS["pusher-key"],
            {
              cluster: admin
                ? SB_ADMIN_SETTINGS["pusher-cluster"]
                : CHAT_SETTINGS["pusher-cluster"],
              authEndpoint: STMBX_URL + "/include/pusher.php",
              auth: {
                params: {
                  login: SBF.loginCookie(),
                },
              },
            }
          );
          SBF.event("SBPusherInit");
        });
      }
    },

    // Initialize Push notifications
    initPushNotifications: function () {
      if (activeUser() || admin) {
        $.getScript(
          "https://js.pusher.com/beams/1.0/push-notifications-cdn.js",
          () => {
            window.navigator.serviceWorker.ready.then(
              (serviceWorkerRegistration) => {
                this.pusher_beams = new PusherPushNotifications.Client({
                  instanceId: admin
                    ? SB_ADMIN_SETTINGS["push-notifications-id"]
                    : CHAT_SETTINGS["push-notifications-id"],
                  serviceWorkerRegistration: serviceWorkerRegistration,
                });
                this.pusher_beams
                  .start()
                  .then((beamsClient) => beamsClient.getDeviceId())
                  .then((deviceId) =>
                    console.log(
                      "Successfully registered with Beams. Device ID:",
                      deviceId
                    )
                  )
                  .then(() =>
                    this.pusher_beams.setDeviceInterests(
                      admin
                        ? [SB_ACTIVE_AGENT["id"], "agents"]
                        : [activeUser().id, "users"]
                    )
                  )
                  .catch(console.error);
                this.init_push_notifications = false;
              }
            );
          }
        );
      }
    },

    // Initialize service worker
    initServiceWorker: function () {
      if ("serviceWorker" in navigator) {
        navigator.serviceWorker
          .register(
            admin || typeof SB_CLOUD_SW != "undefined"
              ? STMBX_URL + "/sw.js?v=" + version
              : CHAT_SETTINGS["push-notifications-url"] + "?v=" + version
          )
          .then((registration) => {
            registration.update();
            this.sw = registration;
          })
          .catch(function (error) {
            console.warn(error);
          });
        navigator.serviceWorker.onmessage = function (e) {
          if (admin) {
            if (e.data.conversation_id) {
              SBAdmin.conversations.openConversation(
                e.data.conversation_id,
                e.data.user_id
              );
              SBAdmin.conversations.update();
            } else if (e.data.user_id) {
              SBAdmin.profile.show(e.data.user_id);
              SBChat.audio.play();
            }
          } else if (e.data == "sb-open-chat") {
            SBChat.open();
          }
        };
      }
    },

    // Start Pusher and Push notifications
    start: function () {
      if (!admin && !this.started && activeUser()) {
        if (this.active) {
          this.init(() => {
            this.event("client-typing", (response) => {
              if (
                response.user_id == SBChat.agent_id &&
                SBChat.conversation &&
                response.conversation_id == SBChat.conversation.id
              ) {
                SBChat.typing(-1, "start");
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                  SBChat.typing(-1, "stop");
                }, 1000);
              }
            });
            this.event("new-message", (response) => {
              if (
                response &&
                activeUser() &&
                !SBF.null(response.conversation_id) &&
                !activeUser().getConversationByID(response.conversation_id) &&
                (!SBChat.conversation ||
                  SBChat.conversation.id != response.conversation_id)
              ) {
                SBChat.updateConversations();
              } else {
                SBChat.update();
              }
            });

            this.presence(1, () => {
              this.started = true;
              SBChat.automations.run_all();
            });
          });
        }
        if (CHAT_SETTINGS["push-notifications"]) {
          if (typeof Notification != ND && Notification.permission == "granted")
            this.initPushNotifications();
          else this.init_push_notifications = true;
        }
      }
    },

    // Subscribe to a channel
    subscribe: function (channel_name, onSuccess = false) {
      if (!this.pusher)
        return this.init(() => {
          this.subscribe(channel_name, onSuccess);
        });
      channel_name = this.cloudChannelRename(channel_name);
      let channel = this.pusher.subscribe(channel_name);
      channel.bind("pusher:subscription_error", (error) => {
        return console.log(error);
      });
      channel.bind("pusher:subscription_succeeded", () => {
        this.channels[channel_name] = channel;
        if (onSuccess) onSuccess();
      });
    },

    // Add event listener for a channel
    event: function (
      event,
      callback,
      channel = "private-user-" + activeUser().id
    ) {
      if (!this.pusher)
        return this.init(() => {
          this.event(event, callback, channel);
        });
      let channel_original = channel;
      channel = this.cloudChannelRename(channel);
      if (channel in this.channels) {
        this.channels[channel].unbind(event);
        this.channels[channel].bind(event, (data) => {
          callback(data);
        });
      } else
        this.subscribe(channel_original, () => {
          this.event(event, callback, channel_original);
        });
    },

    // Trigger an event
    trigger: function (
      event,
      data = {},
      channel = "private-user-" + activeUser().id
    ) {
      if (event.indexOf("client-") == 0) {
        return this.channels[this.cloudChannelRename(channel)].trigger(
          event,
          data
        );
      } else {
        SBF.ajax(
          {
            function: "pusher-trigger",
            channel: channel,
            event: event,
            data: data,
          },
          (response) => {
            return response;
          }
        );
      }
    },

    // Presence
    presence: function (index = 1, onSuccess) {
      if (!this.pusher)
        return this.init(() => {
          this.presence();
        });
      let channel = this.pusher.subscribe(
        this.cloudChannelRename("presence-" + index)
      );
      channel.bind("pusher:subscription_succeeded", (members) => {
        if (members.count > 98) return this.subscribe(index + 1);
        members.each((member) => {
          if (this.presenceCheck(member)) {
            this.online_ids.push(member.id);
          }
        });
        SBChat.updateUsersActivity();
        if (onSuccess) onSuccess();
      });
      channel.bind("pusher:subscription_error", (error) => {
        return console.log(error);
      });
      channel.bind("pusher:member_added", (member) => {
        if (this.presenceCheck(member)) {
          this.presenceAdd(member.id);
        }
        if (admin) SBAdmin.users.onlineUserNotification(member);
      });
      channel.bind("pusher:member_removed", (member) => {
        this.presenceRemove(member.id);
      });
      this.channels_presence.push(channel);
      // if (!admin && CHAT_SETTINGS["slack-active"]) {
      //   this.event("add-user-presence", (response) => {
      //     this.presenceAdd(response.agent_id);
      //   });
      //   SBF.ajax(
      //     {
      //       function: "slack-presence",
      //       list: true,
      //     },
      //     (response) => {
      //       for (var i = 0; i < response.length; i++) {
      //         this.presenceAdd(response[i]);
      //       }
      //       SBChat.updateUsersActivity();
      //     }
      //   );
      // }
    },

    presenceCheck: function (member) {
      let agent = SBF.isAgent(member.info.user_type);
      return (
        ((admin && !agent) || (!admin && agent)) &&
        !this.online_ids.includes(member.id)
      );
    },

    presenceAdd: function (user_id) {
      if (typeof user_id != ND && !this.online_ids.includes(user_id)) {
        this.online_ids.push(user_id);
        this.presenceUpdateAdmin(user_id);
        SBChat.updateUsersActivity();
      }
    },

    presenceRemove: function (user_id) {
      if (typeof user_id == ND) return;
      let index = this.online_ids.indexOf(user_id);
      if (index !== -1) {
        this.online_ids.splice(index, 1);
        this.presenceUpdateAdmin(user_id);
        SBChat.updateUsersActivity();
      } else if (admin) {
        global.find(`.sb-conversation-busy[data-agent="${user_id}"]`).remove();
      }
    },

    presenceUnsubscribe: function () {
      for (var i = 0; i < this.channels_presence.length; i++) {
        this.channels_presence[i].unsubscribe(
          this.cloudChannelRename("presence-" + (i + 1))
        );
      }
    },

    presenceUpdateAdmin: function (user_id) {
      if (admin) {
        if (global.find(".sb-area-users.sb-active").length)
          SBAdmin.users.update();
        if (activeUser() && activeUser().id == user_id)
          SBAdmin.users.updateUsersActivity();
      }
    },

    pushNotification: function (message) {
      let icon = admin ? SB_ACTIVE_AGENT["profile_image"] : activeUser().image;
      SBF.ajax(
        {
          function: "push-notification",
          title: admin ? SB_ACTIVE_AGENT["full_name"] : activeUser().name,
          message: new SBMessage().strip(message),
          icon:
            icon.indexOf("user.svg") > 0
              ? CHAT_SETTINGS["notifications-icon"]
              : icon,
          interests: SBChat.getRecipientUserID(),
          conversation_id:
            SBChat.conversation == false ? false : SBChat.conversation.id,
        },
        (response) => {
          return response;
        }
      );
    },

    cloudChannelRename: function (channel) {
      return CHAT_SETTINGS.cloud || (admin && SB_ADMIN_SETTINGS.cloud)
        ? channel +
            "-" +
            (admin
              ? SB_ADMIN_SETTINGS.cloud.cloud_user_id
              : CHAT_SETTINGS.cloud.cloud_user_id)
        : channel;
    },
  };

  /*
   * ----------------------------------------------------------
   * GLOBAL FUNCTIONS
   * ----------------------------------------------------------
   */

  window.SBF = SBF;
  window.SBPusher = SBPusher;
  window.sb_current_user = sb_current_user;

  /*
   * ----------------------------------------------------------
   * JQUERY FUNCTIONS
   * ----------------------------------------------------------
   */

  // Check if active
  $.fn.sbActive = function (show = -1) {
    if (show === -1) return $(this).hasClass("sb-active");
    $(this).setClass("sb-active", show);
    return this;
  };

  // Loading check, set and unset
  $.fn.sbLoading = function (value = "check") {
    if (value == "check") {
      return $(this).hasClass("sb-loading");
    } else {
      $(this).setClass("sb-loading", value);
    }
    return this;
  };

  // Popup
  $.fn.sbTogglePopup = function (button = false) {
    let showed = true;
    if (admin) SBAdmin.open_popup = false;
    if ($(this).sbActive()) {
      $(this).sbActive(false);
      global.removeClass("sb-popup-active");
      showed = false;
    } else {
      global.addClass("sb-popup-active");
      global.find(".sb-popup").sbActive(false);
      if (button) {
        $(this).css("left", "325.6px"); // Fixed left position
        $(this).sbActive(true);
      }
      if (admin) {
        setTimeout(() => {
          SBAdmin.open_popup = this;
        }, 500);
      }
      SBF.deselectAll();
    }
    return showed;
  };

  // Uploader
  $.fn.sbUploadFiles = function (onSuccess) {
    let files = $(this).prop("files");
    for (var i = 0; i < files.length; i++) {
      let file = files[i];
      let form = new FormData();
      form.append("file", file);
      SBF.upload(form, onSuccess);
    }
    $(this).value = "";
  };

  // Set profile box
  $.fn.setProfile = function (name = false, profile_image = false) {
    if (SBF.null(name)) name = activeUser() ? activeUser().name : "";
    if (SBF.null(profile_image))
      profile_image = activeUser()
        ? activeUser().image
        : STMBX_URL + "/media/user.svg";
    $(this).find("img").attr("src", profile_image);
    $(this).find(".sb-name").html(name);
    return this;
  };

  // Add or remove a class
  $.fn.setClass = function (class_name, add = true) {
    if (add) {
      $(this).addClass(class_name);
    } else {
      $(this).removeClass(class_name);
    }
    return this;
  };

  /*
   * ----------------------------------------------------------
   * CLASSIC FUNCTIONS
   * ----------------------------------------------------------
   */

  // Delta value
  function sbDelta(e) {
    let delta = e.originalEvent.wheelDelta;
    if (typeof delta == ND) {
      delta = e.originalEvent.deltaY;
    }
    if (typeof delta == ND) {
      delta = e.originalEvent.detail * -1;
    }
    return delta;
  }

  // Check if an elemen is loading and set it status to loading
  function loading(element) {
    if ($(element).sbLoading()) return true;
    else $(element).sbLoading(true);
    return false;
  }

  // Shortcut for local storage function
  function storage(key, value = ND) {
    return SBF.storage(key, value);
  }

  // Routin.bot js translations
  function sb_(string) {
    return SBF.translate(string);
  }

  // Access the global user variable
  function activeUser(value = -1) {
    if (value === -1) {
      return window.sb_current_user;
    } else {
      window.sb_current_user = value;
    }
  }

  /*
   * ----------------------------------------------------------
   * # USER
   * ----------------------------------------------------------
   */

  class SBUser {
    constructor(details = {}, extra = {}) {
      this.details = details;
      this.extra = extra;
      this.conversations = [];
      this.processArray(details);
    }

    get id() {
      return this.get("id") == "" ? this.get("user_id") : this.get("id");
    }

    get type() {
      return this.get("user_type");
    }

    get name() {
      return "first_name" in this.details
        ? this.details["first_name"] +
            (this.details["last_name"] ? " " + this.details["last_name"] : "")
        : "";
    }

    get nameBeautified() {
      return "last_name" in this.details &&
        this.details["last_name"].charAt(0) != "#"
        ? this.name
        : CHAT_SETTINGS["visitor-default-name"];
    }

    get image() {
      return this.get("profile_image");
    }

    get language() {
      let language = this.getExtra("language");
      if (!language) language = this.getExtra("browser_language");
      return !language ? "" : language["value"].toLowerCase();
    }

    get(id) {
      if (id in this.details && !SBF.null(this.details[id]))
        return this.details[id];
      else return "";
    }

    getExtra(id) {
      if (id in this.extra && !SBF.null(this.extra[id])) return this.extra[id];
      else return "";
    }

    set(id, value) {
      this.details[id] = value;
    }

    setExtra(id, value) {
      this.extra[id] = value;
    }

    // Initialization
    processArray(details) {
      if (details && "details" in details) {
        for (var i = 0; i < details["details"].length; i++) {
          this.setExtra(details["details"][i]["slug"], details["details"][i]);
        }
        delete details["details"];
        this.details = details;
      }
    }

    // Update the user details and extra details
    update(onSuccess) {
      if (this.id) {
        SBF.ajax(
          {
            function: "get-user",
            user_id: this.id,
            extra: true,
          },
          (response) => {
            this.processArray(response);
            onSuccess();
          }
        );
      } else {
        SBF.error("Missing user ID", "SBUser.update");
      }
    }

    // Get user conversations
    getConversations(onSuccess = false, exclude_id) {
      if (this.id) {
        SBF.ajax(
          {
            function: "get-user-conversations",
            user_id: this.id,
            exclude_id: exclude_id,
            agent: SBF.isAgent(this.type),
          },
          (response) => {
            let conversations = [];
            for (var i = 0; i < response.length; i++) {
              let status = response[i]["conversation_status_code"];
              if (status != 3 || !CHAT_SETTINGS["close-chat"]) {
                let conversation = new SBConversation(
                  [new SBMessage(response[i])],
                  {
                    id: response[i]["conversation_id"],
                    conversation_status_code: status,
                    department: response[i]["department"],
                    agent_id: response[i]["agent_id"],
                    title: response[i]["title"],
                  }
                );
                conversations.push(conversation);
              }
            }
            this.conversations = conversations;
            if (onSuccess) onSuccess(conversations);
          }
        );
      } else {
        SBF.error("Missing user ID", "SBUser.getConversations");
      }
    }

    // Get conversations code
    getConversationsCode(conversations = false) {
      let code = "";
      let active_conversation_id = SBChat.conversation
        ? SBChat.conversation.id
        : -1;
      if (!conversations) conversations = this.conversations;
      for (var i = 0; i < conversations.length; i++) {
        if (conversations[i] instanceof SBConversation) {
          code += `<li ${
            active_conversation_id == conversations[i].id
              ? 'class="sb-active" '
              : ""
          }data-conversation-status="${conversations[i].get(
            "conversation_status_code"
          )}" data-conversation-id="${
            conversations[i].id
          }" data-department="${conversations[i].get(
            "department"
          )}">${conversations[i].getCode()}</li>`;
        } else {
          SBF.error(
            "Conversation not of type SBConversation",
            "SBUser.getConversationsCode"
          );
        }
      }
      return code;
    }

    // Get single conversation
    getFullConversation(
      conversation_id = false,
      onSuccess = false,
      load_chat = 30,
      limit = 30
    ) {
      if (conversation_id !== false) {
        SBF.ajax(
          {
            function: "get-conversation",
            conversation_id: conversation_id,
            load_chat: load_chat,
            limit: limit,
          },
          (response) => {
            let messages = [];
            if (response) {
              if (response === "agent-not-authorized") {
                window.location.href = SBF.URL();
                return;
              }
              for (var i = 0; i < response["messages"].length; i++) {
                total_more = response["total_rows"];
                if (total_more <= load_more) {
                  setTimeout(
                    () => $(".sb-conversation").find(".load-more").hide(),
                    300
                  );
                }
                response["messages"][i]["total_rows"] = response["total_rows"];
                messages.push(new SBMessage(response["messages"][i]));
              }
            }
            if (onSuccess)
              onSuccess(
                new SBConversation(
                  messages,
                  response ? response["details"] : false
                )
              );
          }
        );
      } else {
        SBF.error("Missing conversation ID", "SBUser.getFullConversation");
      }
    }

    getConversationByID(conversation_id, index = false) {
      for (var i = 0; i < this.conversations.length; i++) {
        if (this.conversations[i].id == conversation_id) {
          return index ? i : this.conversations[i];
        }
      }
      return false;
    }

    // Add a new conversation
    addConversation(conversation) {
      if (conversation instanceof SBConversation) {
        let conversation_id = conversation.id;
        let is_new = true;
        for (var i = 0; i < this.conversations.length; i++) {
          if (this.conversations[i].id == conversation_id) {
            this.conversations[i] = conversation;
            is_new = false;
            break;
          }
        }
        if (is_new) {
          this.conversations.unshift(conversation);
        }
        return is_new;
      } else {
        SBF.error(
          "Conversation not of type SBConversation",
          "SBUser.addConversation"
        );
      }
    }

    // Remove a conversation
    removeConversation(conversation_id) {
      let index = this.getConversationByID(conversation_id, true);
      if (index !== false) this.conversations.splice(index, 1);
    }

    // Get the last conversation
    getLastConversation() {
      return this.isConversationsEmpty()
        ? false
        : this.conversations[this.conversations.length - 1];
    }

    // Check if the conversation array is empty
    isConversationsEmpty() {
      return this.conversations.length == 0;
    }

    // Check if the extra array is empty
    isExtraEmpty() {
      return (
        Object.keys(this.extra).length === 0 &&
        this.extra.constructor === Object
      );
    }

    // Delete the user
    delete(onSuccess) {
      if (this.id) {
        SBF.ajax(
          {
            function: "delete-user",
            user_id: this.id,
          },
          () => {
            SBF.event("SBUserDeleted", this.id);
            console.log("delte user");
            onSuccess();
            return true;
          }
        );
      } else {
        SBF.error("Missing user ID", "SBUser.delete");
      }
    }
  }
  window.SBUser = SBUser;

  /*
   * ----------------------------------------------------------
   * # MESSAGE
   * ----------------------------------------------------------
   */

  class SBMessage {
    constructor(details = {}) {
      this.details = details;
      this.linksData = "";
      if ("first_name" in this.details) {
        this.details["full_name"] =
          this.details["first_name"] + " " + this.details["last_name"];
      }
      if (details.message_status_code) {
        details.status_code = details.message_status_code;
      }
      delete details.message_status_code;
      delete details.source;
      delete details.extra;
      delete details.title;
      delete details.tags;
      delete details.labels;
      delete details.agent_id;
      delete details.department;
      let payload = this.get("payload");
      if (payload) {
        try {
          var json = JSON.parse(this.get("payload").replace("\\'", "'"));
          if (json && typeof json === "object") {
            payload = json;
          } else {
            payload = {};
          }
        } catch (e) {
          payload = {};
        }
      } else {
        payload = {};
      }
      this.set("payload", payload);
    }

    get id() {
      return this.get("id");
    }

    get attachments() {
      return !SBF.null(this.details["attachments"])
        ? JSON.parse(this.details["attachments"])
        : [];
    }

    get message() {
      return this.get("message");
    }

    get(id) {
      if (id in this.details && !SBF.null(this.details[id]))
        return this.details[id];
      else return "";
    }

    set(id, value) {
      this.details[id] = value;
    }

    payload(key = false, value = false) {
      let payload = this.get("payload");
      if (key !== false && value !== false) {
        payload[key] = value;
        this.set("payload", payload);
      } else if (key !== false) {
        return key in payload
          ? payload.key
          : "id" in payload && payload.id == key
          ? payload
          : false;
      }
      return payload;
    }

    //load more limit set message
    getLoad() {
      return load_more;
    }
    setLoad(load) {
      load_more = load;
    }

    //Parse message to links data
    getCode(translation = false) {
      //this.hideLoad();
      let agent = SBF.isAgent(this.details["user_type"]);
      let admin_menu = admin ? SBAdmin.conversations.messageMenu(agent) : "";
      let message = translation
        ? this.get("translation")
        : !this.message && "preview" in this.payload
        ? this.payload.preview
        : this.message;
      let attachments = this.attachments;
      let attachments_code = "";
      let media_code = "";
      let thumb =
        (admin && SB_ADMIN_SETTINGS["show-profile-images"]) ||
        (!admin &&
          ((agent && !CHAT_SETTINGS["hide-agents-thumb"]) ||
            (!agent && CHAT_SETTINGS["display-users-thumb"])))
          ? `<div class="sb-thumb"><img loading="lazy" src="${this.details["profile_image"]}"><div class="sb-tooltip"><div>${this.details["full_name"]}</div></div></div>`
          : "";
      let css =
        (!agent ? "" : "sb-right") + (thumb == "" ? "" : " sb-thumb-active");

      let type = "";
      if (!message && !attachments.length) return "";

      var location = "";
      const payload = this.payload();
      if (payload.latitude && payload.longitude) {
        var location = `${payload.latitude},${payload.longitude}`;
      }

      // Rich Messages
      let pattern = new RegExp("^([{,+0-9]+[@s.whatsapp.net])");
      let reply = pattern.test(message);
      if (agent && !reply) {
        let shortcodes = message.match(/\[.+?\]/g) || [];
        let rich = false;
        let count = shortcodes.length;
        for (var i = 0; i < count; i++) {
          let settings = SBRichMessages.shortcode(shortcodes[i]);
          let rich_message = SBRichMessages.generate(settings[1], settings[0]);
          if (rich_message) {
            if (message.charAt(0) != "[") {
              rich_message = rich_message.replace(
                "sb-rich-message",
                "sb-rich-message sb-margin-top"
              );
            }
            if (message.charAt(message.length - 1) != "]") {
              rich_message = rich_message.replace(
                "sb-rich-message",
                "sb-rich-message sb-margin-bottom"
              );
            }
            message = message.replace(shortcodes[i], "{{RM}}");
            message = this.render(message).replace("{{RM}}", rich_message);
            rich = true;
            type = `data-type="${settings[0]}"`;
          }
        }
        if (rich) {
          css += " sb-rich-cnt";
          if (count > 1) {
            type = 'data-type="multiple"';
          }
        }
        if (!count) message = this.render(message);
      } else message = this.render(message);

      // Attachments
      if (attachments.length) {
        attachments_code =
          '<div style="padding-top: 0px" class="sb-message-attachments">';
        for (var i = 0; i < attachments.length; i++) {
          let url = attachments[i][1];
          if (/.jpg|.jpeg|.png|.webp|.gif/.test(url)) {
            let imgStyle = url.includes(".webp")
              ? "box-shadow:none;width: 100px;"
              : "width: auto; height: auto;";
            media_code += `<div style="display:flex;margin-bottom: 6px;flex-wrap: wrap;flex-direction: column;" class="sb-image${
              url.includes(".png") ? " sb-image-png" : ""
            }"><img style="object-fit: cover; border-radius: 8px; ${imgStyle}" loading="lazy" src="${url}" /></div>`;
          } else if (
            attachments.toString().substr(attachments.length - 4) === "oga" ||
            attachments.toString().substr(attachments.length - 4) === "mp3" ||
            attachments.toString().substr(attachments.length - 4) === "ogg" ||
            attachments.toString().substr(attachments.length - 4) === "amr"
          ) {
            media_code += `<audio controls style="max-width:100%;border-radius:8px;margin-bottom: 8px;background:#f1f3f4;"><source src="${url}" type="audio/mpeg"></audio></a>`;
          } else if (
            attachments.toString().substr(attachments.length - 4) === "mp4"
          ) {
            media_code += `<video width="auto" controls style="object-fit: cover;width:100%;border-radius:var(--chat-rounded-size-8);min-width:150px"><source src="${url}"  type="video/mp4"></video></a>`;
          } else {
            media_code += `${
              url
                ? `<a rel="noopener" style="padding-right: var(--chat-spacing-size-1-4);text-decoration:none;padding-left: var(--chat-spacing-size-5);" target="_blank" class="sb-message" href="${url}"><i class="bi-file-text"></i> ${attachments[i][0]}</a>`
                : " "
            }`;
          }
        }
        attachments_code += "</div>";
      }

      var code = "";
      if (message.includes("〚")) {
        var telf = activeUser().getExtra("phone").value;
        var tel = Number(telf);
        var alt = "";

        if (tel > 0) {
          if (message.includes(telf)) {
            message = message.replace(telf, name);
          } else {
            var ind = message.indexOf("}");
            message = message.substring(ind + 1);
            alt = "alt";
            message = message;
          }
        } else {
          telf = $(document).find(
            "body > div > main > div.sb-active.sb-area-conversations > div > div.sb-user-details.sb-top > div.sb-scroll-area > div.sb-profile-list.sb-profile-list-conversation > ul > li:nth-child(8) > label"
          ).innerText;
          if (message.includes(telf)) {
            message = message.replace(telf, name);
          } else {
            var ind = message.indexOf("}");
            message = message.substring(ind + 1);
            alt = "alt";
            message = message;
          }
        }

        // Replace the placeholder with the proper div and ID
        message = message.replace(/<br\s*\/?>/, "");

        message = message.replace(
          "〚",
          alt == "alt" ? `<div id='sb-reply-to-alt'>` : `<div id='sb-reply-to'>`
        );
        message = message.replace(
          "〛",
          `</div><p style='padding: 0px 4px; margin:0px; cursor:pointer;'>`
        );
        message = message.replace(/\|(?=\n)/, `</div>`);
        message = message.replace(/<p\s*\/?>\s*<p\s*\/?>/g, "");
        message = message.replace("{", "");
        message = message.replace("}", "");
        message = message.replace("@s.whatsapp.net}", "");
        message = message.replace(
          "→Forwarded←",
          '<i class="bi bi-arrow-right-square"></i> '
        );

        // Ensure the name is not displayed at the top of the message
        if (message.startsWith(name)) {
          message = message.substring(name.length).trim();
        }

        // Convert dashes with space to bullets
        code = `<div data-id="${
          this.details["id"]
        }" ${type} class="sb-shadow-conversation ${css}">${thumb}
              <div class="sb-cnt" style="min-width:80px; max-width:100%; padding:4px 4px;">
                <div class="sb-message" data-value="${
                  this.linksData
                    ? encodeURI(this.linksData.message)
                    : encodeURI(message)
                }">
                  ${media_code}
                  <div style="max-width:30rem; margin:0px; text-align:start; cursor:pointer;" class="readThis">
                    ${
                      this.linksData
                        ? this.linksData.message
                        : message.replace(/\|/g, " ")
                    }
                  </div>
                </div>
                ${attachments_code}
                <div class="menu-bubble">
                  <div class="events"></div>
                  <div class="sb-time">
                    ${SBF.beautifyTime(this.details["creation_time"], true)}
                  </div>
                </div>
              </div>
              ${admin_menu}
            </div>`;
      } else {
        // MESSAGE CREATION CHAT
        code = `
              <div data-id="${
                this.details["id"]
              }" ${type} class="sb-shadow-conversation ${css}" style="transition: 0.3s all">
                ${thumb}
                  <div class="server-response">
                    <i class="bi-check2-all"></i>
                  </div>
                  <div class="sb-cnt" style="width:fit-content;margin:6px;">
                        <div class="sb-message" data-value="${this.linksData}">
                            ${media_code}
                            <div style="padding-right: var(--chat-spacing-size-1-4);text-decoration:none;padding-left: var(--chat-spacing-size-5);">
                            ${
                              this.linksData
                                ? this.linksData.message
                                : message.replace(/\|/g, " ")
                            }
                            </div>
                        </div>
                        ${attachments_code}
                        <div class="menu-bubble">
                            <div class="sb-time">
                                ${SBF.beautifyTime(
                                  this.details["creation_time"],
                                  true
                                )}
                            </div>
                        </div>
                  </div>
                  ${admin_menu}
              </div>`;
      }
      return code;
    }

    // RENDER BUBBLE MESSAGE
    render(message = false) {
      if (message === false) message = "" + this.details["message"];
      let len = message.length;

      message =
        message.replace(
          /- /g,
          '<li style="margin-left:5px;margin-right:0px">'
        ) + "</li>";

      // Breakline
      message = message.replace(/(?:\r\n|\r|\n)/g, "<br>");

      // Code block
      let codes = message.match(/```([\s\S]+?)```/g) || [];
      for (var i = 0; i < codes.length; i++) {
        let codePlaceholder = `{{code_placeholder_${i}}}`;
        message = message.replace(codes[i], codePlaceholder);
      }

      // Inline code blocks with single backticks
      let inlineCodes = message.match(/`([^`]+)`/g) || [];
      for (var i = 0; i < inlineCodes.length; i++) {
        let codePlaceholder = `{{inline_code_placeholder_${i}}}`;
        message = message.replace(inlineCodes[i], codePlaceholder);
      }
      message = message.replace(
        /(?<!\*)\*(.*?)\*(?!\*)/g,
        "<strong>$1</strong>"
      );
      message = message.replace(/@s\.whatsapp\.net/g, "");
      message = message.replace('["', "<div class='group-chat-reply'>");
      message = message.replace('"]', "</div>");
      message = message.replace(
        "→",
        '<small style="color:var(--chat-text-tertiary)"><i class="bi bi-arrow-right-square"></i> '
      );
      message = message.replace("←", "</small>");
      message = message.replace("←", "</small>");
      message = message.replace(/(^|\s)\_([^\_]+)\_/g, "$1<em>$2</em>");
      message = message.replace(
        /(<li>\s*)?~([^~]+)~/g,
        function (match, liPrefix, content) {
          if (liPrefix) {
            return `<li>${liPrefix}<del>${content}</del>`;
          } else {
            return `<del>${content}</del>`;
          }
        }
      );

      // Single emoji
      if (
        ((len == 6 || len == 5) && message.startsWith("&#x")) ||
        (len < 3 &&
          message.match(
            /(?:[\u2700-\u27bf]|(?:\ud83c[\udde6-\uddff]){2}|[\ud800-\udbff][\udc00-\udfff]|[\u0023-\u0039]\ufe0f?\u20e3|\u3299|\u3297|\u303d|\u3030|\u24c2|\ud83c[\udd70-\udd71]|\ud83c[\udd7e-\udd7f]|\ud83c\udd8e|\ud83c[\udd91-\udd9a]|\ud83c[\udde6-\uddff]|\ud83c[\ude01-\ude02]|\ud83c\ude1a|\ud83c\ude2f|\ud83c[\ude32-\ude3a]|\ud83c[\ude50-\ude51]|\u203c|\u2049|[\u25aa-\u25ab]|\u25b6|\u25c0|[\u25fb-\u25fe]|\u00a9|\u00ae|\u2122|\u2139|\ud83c\udc04|[\u2600-\u26FF]|\u2b05|\u2b06|\u2b07|\u2b1b|\u2b1c|\u2b50|\u2b55|\u231a|\u231b|\u2328|\u23cf|[\u23e9-\u23f3]|[\u23f8-\u23fa]|\ud83c\udccf|\u2934|\u2935|[\u2190-\u21ff])/
          ))
      ) {
        message = `<span class="emoji-large">${message}</span>`;
      }

      // Links
      if (message.includes("http")) {
        message = message.autoLink({
          target: "_blank",
        });
      }

      // Inline code block restore
      for (var i = 0; i < inlineCodes.length; i++) {
        let codePlaceholder = `{{inline_code_placeholder_${i}}}`;
        message = message.replace(
          codePlaceholder,
          `<p style="font-family: monospace;">${inlineCodes[i].substring(
            1,
            inlineCodes[i].length - 1
          )}</p>`
        );
      }

      // Code block restore
      for (var i = 0; i < codes.length; i++) {
        let codePlaceholder = `{{code_placeholder_${i}}}`;
        message = message.replace(
          codePlaceholder,
          `<code>${codes[i].substring(3, codes[i].length - 3)}</code>`
        );
      }

      return message.replace(/&amp;lt;/g, "&lt;");
    }

    strip(message = false) {
      if (message === false) message = "" + this.details["message"];

      let regex = /https:\/\/maps\.google\.com\/.*\b/g;
      message = message.replace(
        regex,
        (match) =>
          `<i class="bi-send" style="vertical-align:middle;padding-left:10px;"></i> <span style="color: #2196F3;">${sb_(
            "Location"
          )}</span>`
      );

      return message;
    }
  }

  window.SBMessage = SBMessage;

  /*
   * ----------------------------------------------------------
   * # CONVERSATION
   * ----------------------------------------------------------
   */

  class SBConversation {
    constructor(messages, details) {
      this.details = SBF.null(details) ? {} : details;
      if (Array.isArray(messages)) {
        this.messages = [];
        if (messages.length) {
          if (messages[0] instanceof SBMessage) {
            this.messages = messages;
          } else {
            SBF.error(
              "Messages not of type SBMessage",
              "SBConversation.constructor"
            );
          }
        }
      } else {
        SBF.error(
          "Message array not of type Array",
          "SBConversation.constructor"
        );
      }
    }

    get id() {
      return this.get("id") == ""
        ? this.get("conversation_id")
        : this.get("id");
    }

    get(id) {
      if (id in this.details && !SBF.null(this.details[id])) {
        return this.details[id];
      }
      if (id == "title") {
        if (!SBF.null(this.details["first_name"])) {
          return this.details["first_name"] + " " + this.details["last_name"];
        } else if (this.messages.length) {
          return this.messages[0].get("full_name");
        }
      }
      return "";
    }

    set(id, value) {
      this.details[id] = value;
    }

    getMessage(id) {
      for (var i = 0; i < this.messages.length; i++) {
        if (this.messages[i].id == id) {
          this.messages[i].set("index", i);
          return this.messages[i];
        }
      }
      return false;
    }

    getLastMessage() {
      let index = this.messages.length - 1;
      for (var i = index; i > -1; i--) {
        if (this.messages[i].message || this.messages[i].attachments.length)
          return this.messages[i];
      }
      return false;
    }

    getLastUserMessage(index = false, agent = false) {
      if (index === false) index = this.messages.length - 1;
      for (var i = index; i > -1; i--) {
        let message = this.messages[i];
        let user_type = message.get("user_type");
        if (!message.message && !message.attachments.length) continue;
        if (
          (!agent && !SBF.isAgent(user_type)) ||
          (agent === true && (user_type == "agent" || user_type == "admin")) ||
          (agent == "bot" && user_type == "bot") ||
          (agent == "no-bot" && user_type != "bot") ||
          (agent == "all" && SBF.isAgent(user_type))
        ) {
          this.messages[i].set("index", i - 1);
          return this.messages[i];
        }
      }
      return false;
    }

    getUserMessages(user_type = "user") {
      let results = [];
      let checks =
        user_type == "user"
          ? ["visitor", "lead", "user"]
          : user_type == "agents"
          ? ["agent", "admin"]
          : ["bot"];
      for (var i = 0; i < this.messages.length; i++) {
        if (checks.includes(this.messages[i].get("user_type"))) {
          results.push(this.messages[i]);
        }
      }
      return results;
    }

    // this update message added in chat. Like variables..
    updateMessage(id, message) {
      if (message instanceof SBMessage) {
        for (var i = 0; i < this.messages.length; i++) {
          if (this.messages[i].id == id) {
            this.messages[i] = message;
            return true;
          }
        }
      } else {
        SBF.error(
          "Message not of type SBMessage",
          "SBConversation.updateMessage"
        );
      }
      return false;
    }

    addMessages(messages) {
      if (Array.isArray(messages)) {
        for (var i = 0; i < messages.length; i++) {
          if (messages[i] instanceof SBMessage) {
            this.messages.push(messages[i]);
          }
        }
      } else {
        if (messages instanceof SBMessage) {
          this.messages.push(messages);
        } else {
          SBF.error(
            "Messages not of type SBMessage",
            "SBConversation.addMessages()"
          );
        }
      }
      return this;
    }

    getCode() {
      // LEFT CHAT LIST MESSAGES
      let count = this.messages.length;
      if (count) {
        let message = this.messages[count - 1];
        let text = message.message;

        // Process the message with the helper function
        text = processMessage(text);

        if (text.indexOf("[") !== false) {
          let shortcodes = text.match(/\[.+?\]/g) || [];
          if (shortcodes.length) {
            let shortcode = SBRichMessages.shortcode(shortcodes[0]);
            text = text.replace(
              shortcodes[0],
              sb_(
                SBF.null(shortcode[1]["message"])
                  ? SBF.null(shortcode[1]["title"])
                    ? ""
                    : shortcode[1]["title"]
                  : shortcode[1]["message"]
              )
            );
          }
        }
        if (SBF.getReply(text)) {
          const ind = text.indexOf("}");
          text = text.substring(ind + 1);
        }
        let title = this.get("title");
        if (
          !title ||
          (tickets && CHAT_SETTINGS["tickets-conversations-title-user"])
        )
          title = SBF.isAgent(message.get("user_type"))
            ? message.get("full_name")
            : sb_("You");
        return `<div class="sb-conversation-item" data-user-id="${message.get(
          "user_id"
        )}"><img loading="lazy" src="${message.get(
          "profile_image"
        )}"><div><span class="sb-name">${title}</span><span class="sb-time">${SBF.beautifyTime(
          message.get("creation_time")
        )}</span></div><div class="sb-message">${
          text.length > 114 ? text.substr(0, 114) + " ..." : text
        }</div></div>`;
      }
      return "";
    }

    deleteMessage(id) {
      for (var i = 0; i < this.messages.length; i++) {
        if (this.messages[i].id == id) {
          this.messages.splice(i, 1);
          return true;
        }
      }
      return false;
    }

    searchMessages(search, exact_match = false) {
      let results = [];
      for (var i = 0; i < this.messages.length; i++) {
        let message = this.messages[i].message;
        if (
          (exact_match && message == search) ||
          (!exact_match && message.includes(search))
        ) {
          results.push[messages[i]];
        }
      }
      return results;
    }

    getAttachments() {
      let list = [];
      for (var i = 0; i < this.messages.length; i++) {
        let attachments = this.messages[i].attachments;
        for (var j = 0; j < attachments.length; j++) {
          let link = attachments[j][1];
          list.push([
            attachments[j][0],
            link,
            link.substr(link.lastIndexOf(".") + 1),
            this.messages[i].id,
          ]);
        }
      }
      return list;
    }
  }
  window.SBConversation = SBConversation;

  /*
   * ----------------------------------------------------------
   * # CHAT
   * ----------------------------------------------------------
   */

  var SBChat = {
    // Variables
    rich_messages_list: [
      "chips",
      "buttons",
      "select",
      "inputs",
      "table",
      "list",
    ],
    emoji_options: {
      range: 0,
      range_limit: 47,
      list: [],
      list_now: [],
      touch: false,
    },
    initialized: false,
    editor_listening: false,
    conversation: false,
    load_message: [],
    is_busy: false,
    is_busy_update: false,
    is_busy_populate: false,
    chat_open: false,
    real_time: false,
    agent_id: -1,
    agent_online: false,
    user_online: false,
    expanded: false,
    main_header: true,
    start_header: false,
    desktop_notifications: false,
    flash_notifications: false,
    id_last_message: 0,
    id_last_message_conversation: 0,
    datetime_last_message_conversation: "2000-01-01 00:00:00",
    audio: false,
    audio_out: false,
    tab_active: true,
    notifications: [],
    typing_settings: {
      typing: false,
      sent: false,
      timeout: false,
    },
    email_sent: false,
    dashboard: false,
    articles: false,
    articles_categories: false,
    // slack_channel: [-1, -1],
    skip: false,
    queue_interval: false,
    departments: false,
    default_department: null,
    default_agent: null,
    default_tags: null,
    offline_message_set: false,

    // Update the UI to display the active agent of the conversation
    setActiveAgent: function (agent_id) {
      let admin = $(".sb-admin");
      let conversations_area = admin.find(".sb-area-conversations");
      let select = conversations_area.find("#conversation-agent");
      let li = select.find(`[data-id="${agent_id}"]`);
      SBChat.conversation.set("agent_id", agent_id);

      select
        .find(" > p")
        .attr("data-value", li.data("value"))
        .html(li.html())
        .next()
        .sbActive(false);
      if (
        SB_ACTIVE_AGENT["user_type"] == "agent" &&
        (!SB_ADMIN_SETTINGS["assign-conversation-to-agent"] || agent_id)
      ) {
        conversations_admin_list_ul
          .find(`[data-conversation-id="${SBChat.conversation.id}"]`)
          .remove();
        SBConversations.clickFirst();
      }
      if (agent_id)
        SBChat.showResponse("Agent assigned. The agent has been notified.");
    },

    // Update the agent assignged to a conversation
    assignAgent: function (conversation_id, agent_id, onSuccess = false) {
      SBF.ajax(
        {
          function: "update-conversation-agent",
          conversation_id: conversation_id,
          agent_id: agent_id,
          message: SBChat.conversation.getLastMessage().message,
        },
        (response) => {
          if (onSuccess) onSuccess(response);
        }
      );
    },
    // Update the agent assignged to a conversation
    assignAgent: function (conversation_id, agent_id, onSuccess = false) {
      SBF.ajax(
        {
          function: "update-conversation-agent",
          conversation_id: conversation_id,
          agent_id: agent_id,
          message: SBChat.conversation.getLastMessage().message,
        },
        (response) => {
          if (onSuccess) onSuccess(response);
        }
      );
    },
    // Display the bottom card information box
    showResponse: function (text, type = false) {
      let admin = $(".sb-admin");
      let conversations_area = admin.find(".sb-area-conversations");
      var card = admin.find(".sb-info-card");
      if (!type) {
        card.removeClass(
          "sb-info-card-error sb-info-card-warning sb-info-card-info"
        );
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          card.sbActive(false);
        }, 5000);
      } else if (type == "error") {
        card.addClass("sb-info-card-error");
      } else {
        card.addClass("sb-info-card-info");
      }
      card.html(`<h3>${sb_(text)}</h3>`).sbActive(true);
    },
    // Send a message
    sendMessage: function (
      user_id = -1,
      message = "",
      attachments = [],
      onSuccess = false,
      payload = false,
      conversation_status_code = false
    ) {
      let is_dialogflow_human_takeover =
        dialogflow_human_takeover && SBApps.dialogflow.active();
      let is_return = false;

      //assign agent id
      if (SBChat.conversation.id != undefined) {
        activeUser().getFullConversation(SBChat.conversation.id, (response) => {
          if (response.details["agent_id"] != SB_ACTIVE_AGENT["id"]) {
            SBChat.assignAgent(
              SBChat.conversation.id,
              SB_ACTIVE_AGENT["id"],
              () => {
                SBChat.setActiveAgent(SB_ACTIVE_AGENT["id"]);
              }
            );
          }
        });
      }

      // Check settings and contents
      if (!activeUser() && !admin) {
        this.addUserAndLogin(() => {
          return this.sendMessage(
            user_id,
            message,
            attachments,
            onSuccess,
            payload
          );
        }, true);
        return;
      }
      if (!this.conversation) {
        let last_conversation = admin
          ? false
          : activeUser().getLastConversation();

        if (
          last_conversation &&
          force_action != "new-conversation" &&
          (!SBChat.default_department ||
            SBChat.default_department == last_conversation.get("department")) &&
          (!SBChat.default_agent ||
            SBChat.default_agent == last_conversation.get("agent_id"))
        ) {
          this.openConversation(last_conversation.id);
          this.setConversation(last_conversation);
          force_action = false;
        } else {
          this.newConversation(
            conversation_status_code,
            user_id,
            "",
            [],
            admin && SB_ACTIVE_AGENT["department"]
              ? SB_ACTIVE_AGENT["department"]
              : null,
            null,
            () => {
              return this.sendMessage(
                user_id,
                message,
                attachments,
                onSuccess,
                payload
              );
            }
          );
          return;
        }
      }
      this.calculateLabelDateFirst();
      if (user_id == -1)
        user_id = admin ? SB_ACTIVE_AGENT["id"] : activeUser().id;
      let is_user = user_id != bot_id;
      if (!message && !attachments.length) {
        var message = chat_textarea.val().trim();
        chat_editor.find(".sb-attachments > div").each(function () {
          attachments.push([
            $(this).attr("data-name"),
            $(this).attr("data-value"),
            $(this).attr("data-id"),
          ]);
        });
        if (admin && SBAdmin.must_translate) {
          SBApps.dialogflow.translate(
            [message],
            activeUser().language,
            (response) => {
              if (response.length) {
                if (payload) {
                  payload["original-message"] = message;
                } else {
                  payload = {
                    "original-message": message,
                  };
                }
                if (response[0].translatedText)
                  message = response[0].translatedText;
              }

              this.sendMessage(
                user_id,
                message,
                attachments,
                onSuccess,
                payload,
                conversation_status_code
              );
            }
          );
          is_return = true;
        }
      }
      this.busy(true);
      if (is_user) {
        chat_textarea.val("").css("height", "");
        chat_editor.find(".sb-attachments").html("");
      }
      this.activateBar(false);

      if (is_return) return;
      if (conversation_status_code === false && user_id == bot_id) {
        conversation_status_code = "skip";
      }
      if (!admin && is_user && !is_dialogflow_human_takeover) {
        conversation_status_code = 2;
      }

// Helper function to format the message
function formatMessage(message, prependAgentName) {
  return prependAgentName ? `*{agent_name}*\n${message}` : message;
}

// Send message
if (message || attachments.length || payload) {
  // Retrieve the checkbox state from localStorage
  const prependAgentName = localStorage.getItem('agentNameToggle') === 'true';
  const formattedMessage = formatMessage(message, prependAgentName);

  let message_response = {
    user_id: user_id,
    user: activeUser(),
    conversation_id: this.conversation.id,
    conversation: this.conversation,
    conversation_status_code: conversation_status_code,
    message: formattedMessage, // render in chat app
    attachments: attachments,
  };

  SBF.ajax(
    {
      function: "send-message",
      user_id: user_id,
      conversation_id: this.conversation.id,
      message: formattedMessage, // render WhatsApp or chat app message
      attachments: attachments,
      conversation_status_code: conversation_status_code,
      queue: !admin && CHAT_SETTINGS["queue"] && is_user,
      payload: payload,
      recipient_id: admin ? activeUser().id : false,
    },
    (response) => {
      // Update the dashboard conversations area
      if (!admin && user_id == bot_id) {
        if (this.dashboard) {
          this.updateConversations();
        } else if (!this.chat_open) {
          this.updateNotifications("add", this.conversation.id);
        }
      }

      // Update the chat current conversation
      if (
        (admin && !this.user_online) ||
        (!admin && !this.agent_online)
      ) {
        this.update();
      }

      // Follow up and offline messages
      if (!admin && is_user && !dialogflow_human_takeover) {
        this.followUp();
        this.offlineMessage();
      }

      // Dialogflow
      if (
        !admin &&
        is_user &&
        (!payload ||
          (payload["id"] != "sb-human-takeover" &&
            SBF.null(payload["skip-dialogflow"])))
      ) {
        SBApps.dialogflow.message(formattedMessage, attachments);
      }

      // Language detection
      if (
        is_user &&
        CHAT_SETTINGS["language-detection"] &&
        this.conversation &&
        formattedMessage.split(" ").length > 1 &&
        !SBF.storage("language-detection-completed")
      ) {
        SBF.ajax({
          function: "google-language-detection-update-user",
          user_id: user_id,
          string: formattedMessage,
          token: SBApps.dialogflow.token,
        });
        SBF.storage("language-detection-completed", true);
      }

      // Articles
      if (
        this.articles &&
        !admin &&
        CHAT_SETTINGS["articles"] &&
        !CHAT_SETTINGS["office-hours"] &&
        !this.isInitDashboard()
      ) {
        setTimeout(() => {
          if (this.conversation) {
            this.sendMessage(bot_id, "[articles]");
            this.scrollBottom();
            this.articles = false;
          }
        }, 5000);
      }

      // Queue
      if (response["queue"]) {
        this.queue(this.conversation.id);
      }

      // Events
      message_response["message_id"] = response.id;
      SBF.event("SBMessageSent", message_response);
      if (tickets) SBTickets.onMessageSent();
      if (onSuccess) onSuccess(message_response);
      if (response.notifications.length)
        SBF.event("SBNotificationsSent", response.notifications);

      // Miscellaneous
      if (this.skip) this.skip = false;
      this.busy(false);
    }
  );

  // Display the message as sending in progress
  if (is_user) {
    const escapedMessage = SBF.escape(formattedMessage);
    chat.append(
      new SBMessage({
        id: "sending",
        profile_image: admin
          ? SB_ACTIVE_AGENT["profile_image"]
          : activeUser().image,
        full_name: activeUser().name,
        creation_time: "0000-00-00 00:00:00",
        message: escapedMessage,
        user_type: admin ? "agent" : "user",
      })
        .getCode()
        .replace(
          '<div class="sb-time"></div>',
          `<div class="sb-time">${sb_("Sending")}<i></i></div>`
        )
    );
    if (!this.dashboard) this.scrollBottom();
  }

  // Sounds
  if (
    (this.audio &&
      !admin &&
      this.chat_open &&
      is_user &&
      ["a", "aa"].includes(CHAT_SETTINGS["chat-sound"])) ||
    (admin && SB_ADMIN_SETTINGS["sounds"] == "a")
  ) {
    this.audio_out.play();
  }
} else this.busy(false);
    },

    // [Deprecated] This function will be removed soon
    sendBotMessage: function (message = "", attachments = []) {
      return SBApps.dialogflow.message(message, attachments);
    },

    // Update message
    updateMessage: function (message_id, message = "") {
      SBF.ajax({
        function: "update-message",
        message_id: message_id,
        message: message,
      });
    },

    // Email notifications
    sendEmail: function (message, attachments, send_to_active_user = false) {
      let recipient_id = send_to_active_user
        ? activeUser().id
        : this.getRecipientUserID();
      if (!admin && !isNaN(recipient_id) && this.agent_online) {
        return false;
      }
      SBF.ajax(
        {
          function: "create-email",
          recipient_id: recipient_id,
          sender_name: admin
            ? send_to_active_user
              ? SB_ACTIVE_AGENT["full_name"]
              : activeUser().name
            : send_to_active_user
            ? CHAT_SETTINGS["bot-name"]
            : activeUser().name,
          sender_profile_image: admin
            ? send_to_active_user
              ? SB_ACTIVE_AGENT["profile_image"]
              : activeUser().name
            : send_to_active_user
            ? CHAT_SETTINGS["bot-image"]
            : activeUser().image,
          message: message,
          attachments: attachments,
          department: this.conversation
            ? this.conversation.get("department")
            : false,
          conversation_id: this.conversation ? this.conversation.id : false,
        },
        () => {
          SBF.event("SBEmailSent", {
            recipient_id: recipient_id,
            message: message,
            attachments: attachments,
          });
        }
      );
    },

    // SMS notifications
    sendSMS: function (message) {
      let recipient_id = this.getRecipientUserID();
      if (!admin && !isNaN(recipient_id) && this.agent_online) return false;
      SBF.ajax(
        {
          function: "send-sms",
          to: recipient_id,
          message: message,
          conversation_id: this.conversation ? this.conversation.id : false,
        },
        (response) => {
          if (response["status"] == "sent" || response["status"] == "queued") {
            SBF.event("SBSMSSent", {
              recipient_id: this.getRecipientUserID(),
              message: message,
              response: response,
            });
          } else if (response.message) {
            SBF.error(response.message, "SBChat.sendSMS");
          }
        }
      );
    },

    // Desktop notifications
    // desktopNotification: function (
    //   title,
    //   message,
    //   icon,
    //   conversation_id = false,
    //   user_id = false
    // ) {
    //   if (Notification.permission !== "granted") {
    //     Notification.requestPermission();
    //   } else {
    //     let formattedMessage = message
    //       .replace(/\*(.*?)\*/g, "\u200E*$1*\u200E") // bold
    //       .replace(/_(.*?)_/g, "\u200E_$1_\u200E") // italic
    //       .replace(/~(.*?)~/g, "\u200E~$1~\u200E") // strikethrough
    //       .replace(/```(.*?)```/g, "\u200E```\n$1\n```\u200E") // code block
    //       .replace(/`(.*?)`/g, "\u200E`$1`\u200E") // inline code
    //       .replace(/^([{,+,0-9,}]+[@s.whatsapp.net])/g, "\u200E$1") // Replace the given pattern
    //       .replace('["', "<div class='group-chat-reply'>")
    //       .replace('"]', "</div>")
    //       .replace(
    //         "→",
    //         '<small style="color:var(--chat-text-tertiary)"><i class="bi bi-arrow-right-square"></i> '
    //       )
    //       .replace("←", "</small>")
    //       .replace("←", "</small>");

    //       //this code above format in chat conversation whatsapp web

    //     if (!formattedMessage) {
    //       formattedMessage = "📄";
    //     }

    //     let notify = SBPusher.sw.showNotification(title, {
    //       body: new SBMessage().strip(formattedMessage),
    //       icon:
    //         icon.indexOf("user.svg") > 0
    //           ? CHAT_SETTINGS["notifications-icon"]
    //           : icon,
    //     });
    //     notify.onclick = () => {
    //       if (admin) {
    //         if (conversation_id) {
    //           SBAdmin.conversations.openConversation(
    //             conversation_id,
    //             user_id == false ? activeUser().id : user_id
    //           );
    //           SBAdmin.conversations.update();
    //         } else if (user_id) {
    //           SBAdmin.profile.show(user_id);
    //         }
    //       } else {
    //         this.start();
    //       }
    //       window.focus();
    //     };
    //   }
    // },

    desktopNotification: function (
      title,
      message,
      icon,
      conversation_id = false,
      user_id = false
    ) {
      if (Notification.permission !== "granted") {
        Notification.requestPermission();
      } else {
        // Use processMessage to format the message
        let formattedMessage = processMessage(message);

        if (!formattedMessage) {
          formattedMessage = "📄";
        }

        let notify = SBPusher.sw.showNotification(title, {
          body: new SBMessage().strip(formattedMessage),
          icon:
            icon.indexOf("user.svg") > 0
              ? CHAT_SETTINGS["notifications-icon"]
              : icon,
        });
        notify.onclick = () => {
          if (admin) {
            if (conversation_id) {
              SBAdmin.conversations.openConversation(
                conversation_id,
                user_id == false ? activeUser().id : user_id
              );
              SBAdmin.conversations.update();
            } else if (user_id) {
              SBAdmin.profile.show(user_id);
            }
          } else {
            this.start();
          }
          window.focus();
        };
      }
    },

    //alert update note
    updateNote: function (conversation_id, note_id, status, onSuccess = false) {
      SBF.ajax(
        {
          function: "update-note",
          conversation_id: conversation_id,
          note_id: note_id,
          status: status,
        },
        (response) => {
          this.busy = false;
          if (onSuccess) onSuccess(response);
        }
      );
    },

    // Returns the recipient user ID
    getRecipientUserID: function () {
      return admin
        ? activeUser().id
        : this.lastAgent(false)
        ? this.lastAgent(false)["user_id"]
        : SBF.null(this.conversation.get("agent_id"))
        ? SBF.null(this.conversation.get("department"))
          ? "agents"
          : "department-" + this.conversation.get("department")
        : this.conversation.get("agent_id");
    },

    // Editor submit message
    submit: function () {
      if (!this.is_busy) {
        this.sendMessage();
        if (CHAT_SETTINGS["cron-email-piping-active"]) {
          setInterval(function () {
            SBF.ajax({
              function: "email-piping",
            });
          }, 60000);
          CHAT_SETTINGS["cron-email-piping-active"] = false;
        }
        if (SBPusher.init_push_notifications) {
          SBPusher.initPushNotifications();
        }
      }
    },

    // Initialize the chat
    initChat: function () {
      if (admin) return;
      SBF.getActiveUser(true, () => {
        let active = activeUser() !== false;
        let user_type = active ? activeUser().type : false;
        if (
          !tickets &&
          CHAT_SETTINGS["popup"]["active"] &&
          !storage("popup") &&
          (!mobile || !CHAT_SETTINGS["popup-mobile-hidden"])
        ) {
          this.popup();
        }
        SBChat.automations.run_all();
        if (
          !tickets &&
          CHAT_SETTINGS["privacy"] &&
          !CHAT_SETTINGS["registration-required"] &&
          !storage("privacy-approved")
        ) {
          this.privacy();
          return;
        }
        if (
          typeof Notification !== ND &&
          !CHAT_SETTINGS["push-notifications-users"] &&
          (CHAT_SETTINGS["desktop-notifications"] == "all" ||
            CHAT_SETTINGS["desktop-notifications"] == "users" ||
            (admin && CHAT_SETTINGS["desktop-notifications"] == "agents"))
        ) {
          this.desktop_notifications = true;
        }
        if (
          CHAT_SETTINGS["flash-notifications"] == "all" ||
          CHAT_SETTINGS["flash-notifications"] == "users" ||
          (admin && CHAT_SETTINGS["flash-notifications"] == "agents")
        ) {
          this.flash_notifications = true;
        }
        if (this.registration(true) && !tickets) {
          this.registration();
          if (!active && CHAT_SETTINGS["visitors-registration"]) {
            this.addUserAndLogin();
          }
          return;
        }
        if (
          !active &&
          (CHAT_SETTINGS["visitors-registration"] ||
            CHAT_SETTINGS["subscribe"] ||
            tickets) &&
          (!tickets || !CHAT_SETTINGS["tickets-registration-required"])
        ) {
          this.addUserAndLogin(() => {
            this.welcome();
            // this.subscribe();
            // // SBApps.woocommerce.waitingList();
            this.finalizeInit();
          });
        } else if (!this.conversation && active) {
          this.populateConversations();
        } else {
          this.finalizeInit();
        }
        if (
          CHAT_SETTINGS["header-name"] &&
          active &&
          user_type == "user" &&
          !tickets
        ) {
          chat_header
            .find(".sb-title")
            .html(`${sb_("Hello")} ${activeUser().nameBeautified}!`);
        }
        this.welcome();
        // this.subscribe();
        if (!SBPusher.active) {
          setInterval(() => {
            this.updateConversations();
            this.updateUsersActivity();
          }, 10200);
        }
        // SBApps.woocommerce.waitingList();
        this.scrollBottom(true);
      });
    },

    finalizeInit: function () {
      if (!this.initialized) {
        main.attr("style", "");
        if (!admin && !tickets) {
          if (this.isInitDashboard()) {
            this.showDashboard();
          }
          if (!mobile && window.innerHeight < 760) {
            main
              .find(" > .sb-body")
              .css("max-height", window.innerHeight - 130 + "px");
          }
        }
        this.initialized = true;
        if (!admin) {
          if (activeUser() && !this.registration(true)) {
            if (storage("open-conversation"))
              this.openConversation(storage("open-conversation"));
            if (SBF.getURL("conversation"))
              this.openConversation(SBF.getURL("conversation"));
          }
          if (
            (!this.chat_open &&
              ((!mobile && storage("chat-open")) ||
                SBF.getURL("chat") == "open")) ||
            SBF.getURL("conversation")
          ) {
            setTimeout(() => {
              this.start();
            }, 500);
          }
          // if (CHAT_SETTINGS["woocommerce-returning-visitor"]) {
          //   if (storage("returning-visitor") === false) {
          //     SBF.storageTime("returning-visitor");
          //   } else if (
          //     SBF.storageTime("returning-visitor", 24) &&
          //     !storage("returning-visitor-processed")
          //   ) {
          //     setTimeout(() => {
          //       SBF.ajax(
          //         {
          //           function: "woocommerce-returning-visitor",
          //         },
          //         () => {
          //           storage("returning-visitor-processed", true);
          //         }
          //       );
          //     }, 15000);
          //   }
          // }
          if (CHAT_SETTINGS["timetable-type"]) SBChat.offlineMessage();
          if (
            CHAT_SETTINGS["queue-human-takeover"] &&
            SBApps.dialogflow.humanTakeoverActive()
          ) {
            CHAT_SETTINGS["queue"] = true;
          }
        }
        if (tickets) SBTickets.init();
        SBF.event("SBInit");
      }
    },

    // Initialize the chat settings and open the chat
    start: function () {
      if (this.initialized) {
        this.populate();
        // this.Loadmore();
        this.headerAgent();
        this.updateUsersActivity();
        this.startRealTime();
        this.chat_open = true;
        this.popup(true);
        if (this.conversation)
          this.updateNotifications("remove", this.conversation.id);
        main.sbActive(true);
        $("body").addClass("sb-chat-open");
        if (CHAT_SETTINGS["welcome-trigger"] == "open") {
          this.welcome();
        }
        this.calculateLabelDates();
      }
    },

    // Open or close the chat
    open: function (open = true) {
      if (open && !this.chat_open) {
        this.start();
        this.chat_open = true;
        this.startRealTime();
        main.sbActive(true);
        $("body").addClass("sb-chat-open");
        storage("chat-open", true);
        if (mobile)
          history.pushState(
            {
              "chat-open": true,
            },
            "",
            ""
          );
        SBF.event("SBChatOpen");
      } else if (!open && this.chat_open) {
        main.sbActive(false);
        this.stopRealTime();
        this.chat_open = false;
        storage("chat-open", false);
        $("body").removeClass("sb-chat-open");
        SBF.event("SBChatClose");
      }
    },

    // Get a full conversation and display it in the chat
    openConversation: function (conversation_id) {
      activeUser().getFullConversation(conversation_id, (response) => {
        if (!response["id"]) {
          storage("open-conversation", "");
          return false;
        }
        this.setConversation(response);
        this.hideDashboard();
        this.populate();
        this.main_header = false;
        if (storage("queue") == conversation_id) {
          this.queue(conversation_id);
        }
        if (this.chat_open || tickets) {
          this.updateNotifications("remove", conversation_id);
        }
        if (tickets) SBTickets.activateConversation(response);
        storage("open-conversation", conversation_id);
        SBF.event("SBConversationOpen", response);
      });
    },

    //load more message add show
    loadUpdate: function (response) {
      let count = response.length;
      let code = "";
      let id_check = [];
      let last_date = false;

      // Load More messages
      this.calculateLabelDateFirst();
      for (var i = 0; i < count; i++) {
        if (!id_check.includes(response[i].details["id"])) {
          let message = new SBMessage(response[i].details);
          let payload = message.payload();
          let current_date = SBF.beautifyTime(message.get("creation_time"));
          if (current_date != last_date) {
            code += `<div class="sb-label-date">${current_date}</div>`;
            last_date = current_date;
          }
          code += message.getCode();
          id_check.push(message.id);
        }
      }
      $(".sb-list .sb-label-date:nth-child(2)").after(code);
      $(".sb-list .sb-label-date:nth-child(2)").remove();
      if (!this.dashboard) {
        this.calculateLabelDates();
      }
    },
    // Update the active conversation with the latest messages
    update: function () {
      if (this.conversation) {
        if (this.is_busy_update) return;
        let last_message = this.conversation.getLastMessage();
        let is_update = false;
        SBF.ajax(
          {
            function: "get-new-messages",
            conversation_id: this.conversation.id,
            datetime: this.datetime_last_message_conversation,
            last_id: this.id_last_message_conversation,
          },
          (response) => {
            let count = response.length;
            this.is_busy_update = false;

            if (this.conversation) {
              if (
                Array.isArray(response) &&
                count > 0 &&
                (!last_message ||
                  last_message.id != response[count - 1]["id"] ||
                  last_message.message != response[count - 1]["message"] ||
                  last_message.payload != response[count - 1]["payload"] ||
                  last_message.attachments !=
                    response[count - 1]["attachments"])
              ) {
                let code = "";
                let messages = [];
                let id_check = [];
                let dialogflow_activation = false;

                // Generate and add the new messages
                this.calculateLabelDateFirst();
                for (var i = 0; i < count; i++) {
                  if (!id_check.includes(response[i]["id"])) {
                    let message = new SBMessage(response[i]);
                    let payload = message.payload();
                    this.id_last_message_conversation = message.id;
                    this.datetime_last_message_conversation =
                      message.get("creation_time");

                    // Payload
                    if (!["boolean", "string"].includes(typeof payload)) {
                      if ("event" in payload) {
                        let event = payload["event"];
                        if (
                          (event == "delete-message" &&
                            this.conversation.getMessage(message.id) !==
                              false) ||
                          (!admin &&
                            message.message == "" &&
                            !message.attachments.length)
                        ) {
                          this.deleteMessage(message.id);
                        }
                        // if (event == "woocommerce-update-cart" && !admin) {
                        //   SBApps.woocommerce.updateCart(
                        //     payload["action"],
                        //     payload["id"]
                        //   );
                        // }
                        if (
                          !SBApps.dialogflow.active() &&
                          (event == "conversation-status-update-3" ||
                            event == "conversation-status-update-4" ||
                            event == "activate-bot")
                        ) {
                          SBApps.dialogflow.active("activate");
                          dialogflow_activation = true;
                        }
                        if (
                          CHAT_SETTINGS["close-chat"] &&
                          event == "conversation-status-update-3"
                        ) {
                          this.closeChat(false);
                          return;
                        }
                      }
                      if (
                        "human-takeover" in payload &&
                        CHAT_SETTINGS["queue-human-takeover"]
                      ) {
                        CHAT_SETTINGS["queue"] = true;
                        SBChat.queue(SBChat.conversation.id);
                      }
                    }

                    // Message creator for chat
                    if (this.conversation.getMessage(response[i]["id"])) {
                      this.conversation.updateMessage(message.id, message);
                      chat
                        .find(`[data-id="${message.id}"]`)
                        .replaceWith(message.getCode());
                      is_update = true;
                    } else {
                      this.conversation.addMessages(message);
                      //  	let rating = $(element).attr('data-rating');
                      let agent = SBChat.conversation.getLastUserMessage(
                        false,
                        true
                      );
                      if (
                        agent &&
                        message.details.user_id != agent?.get("user_id") &&
                        agent?.get("message") == "[rating]"
                      ) {
                        const rating =
                          message.details.message == 1
                            ? "positive"
                            : "negative";
                        const feedback = SBF.get_value(
                          SBF.admin_set("rate-and-review")["rate-reply"]
                        );
                        var settings = {
                          conversation_id: SBChat.conversation.id,
                          agent_id: agent ? agent.get("user_id") : bot_id,
                          user_id: activeUser().id,
                          source: this.conversation.get("source"),
                          message: message.details.message,
                          rating: rating == "positive" ? 1 : -1,
                        };
                        let payload = {
                          "rich-messages": {},
                        };
                        let rich_id = SBF.random() + message.details.id;
                        payload["rich-messages"][rich_id] = {
                          type: "rating",
                          result: settings,
                        };
                        let parameters = {
                          function: "set-rating",
                          payload: payload,
                          settings: settings,
                          message_id: agent.id,
                          message: feedback,
                        };
                        if (
                          message.details.message == 1 ||
                          message.details.message == 2
                        ) {
                          SBF.ajax(parameters, (response) => {
                            this.conversation.get("source") != "tk"
                              ? SBChat.sendMessage(
                                  SB_ACTIVE_AGENT["id"],
                                  feedback
                                )
                              : "";
                          });
                        }
                      }
                      code += message.getCode();
                    }
                    messages.push(message);
                    id_check.push(message.id);
                  }
                }
                chat.append(code);

                // Update status code
                let last_message = this.conversation.getLastMessage();
                let user_type = last_message.get("user_type");
                let is_agent = SBF.isAgent(user_type);
                if (!admin && is_agent && user_type != "bot") {
                  if (this.chat_open) {
                    if (last_message.message.indexOf("sb-rich-success") == -1)
                      this.setConversationStatus(0);
                    if (CHAT_SETTINGS["follow"]["active"])
                      clearTimeout(timeout);
                  }
                  if (!dialogflow_activation) SBApps.dialogflow.active(false);
                }

                // Queue
                if (
                  storage("queue") == this.conversation.id &&
                  is_agent &&
                  user_type != "bot"
                ) {
                  this.queue("clear");
                }

                // Miscellaneous
                chat.find('[data-id="sending"]').remove();
                this.headerAgent();
                if (!this.dashboard && !is_update) {
                  this.scrollBottom();
                  setTimeout(() => {
                    this.scrollBottom();
                  }, 300);
                }
                if (this.dashboard || !this.chat_open) {
                  this.updateNotifications("add", this.conversation.id);
                }
                this.typing(-1, "stop");
                this.busy(false);
                if (
                  this.audio &&
                  (count != 1 ||
                    !(
                      SBF.null(messages[0].message) &&
                      SBF.null(messages[0].attachments)
                    )) &&
                  ((!admin &&
                    this.chat_open &&
                    (is_agent || user_type == "bot") &&
                    ["aa", "ia"].includes(CHAT_SETTINGS["chat-sound"])) ||
                    (admin &&
                      !SBF.isAgent(user_type) &&
                      ["a", "i", "ic"].includes(SB_ADMIN_SETTINGS["sounds"])))
                ) {
                  this.audio.play();
                }
                SBF.event("SBNewMessagesReceived", messages);
                if (tickets) SBTickets.onNewMessageReceived(messages[0]);
              }
            }
          }
        );
        this.is_busy_update = true;
        setTimeout(() => {
          this.is_busy_update = false;
        }, 5000);
      } else {
        this.updateConversations();
      }
    },

    // Update the user conversations list with the latest conversations and messages
    updateConversations: function () {
      if (activeUser()) {
        SBF.ajax(
          {
            function: "get-new-user-conversations",
            datetime: this.id_last_message,
          },
          (response) => {
            if (response.length) {
              this.id_last_message = response[0]["id"];
              for (var i = 0; i < response.length; i++) {
                let conversation_id = response[i]["conversation_id"];
                let message = new SBMessage(response[i]);
                let status_code = response[i]["conversation_status_code"];
                let conversation = new SBConversation([message], {
                  id: conversation_id,
                  conversation_status_code: status_code,
                  department: response[i]["department"],
                  title: response[i]["title"],
                });
                let is_new = activeUser().addConversation(conversation);

                // Red notifications
                if (
                  response[i]["user_id"] != activeUser().id &&
                  (this.conversation.id != conversation_id || !this.chat_open)
                ) {
                  this.updateNotifications("add", conversation_id);
                  if (CHAT_SETTINGS["auto-open"]) {
                    this.start();
                  }
                }

                // Payload
                let payload = message.payload();
                if (typeof payload !== "boolean" && "event" in payload) {
                  let event = payload["event"];
                  if (event == "open-chat") {
                    if (mobile) {
                      this.open(false);
                    } else {
                      if (
                        this.conversation.id != conversation_id ||
                        this.dashboard
                      ) {
                        this.openConversation(conversation_id);
                      }
                      setTimeout(() => {
                        this.open();
                      }, 500);
                    }
                  }
                  if (message.message == "" && !message.attachments.length) {
                    continue;
                  }
                }

                if (!this.tab_active) {
                  // Desktop notifications
                  if (this.desktop_notifications) {
                    SBChat.desktopNotification(
                      message.get("full_name"),
                      message.message,
                      message.get("profile_image")
                    );
                  }

                  // Tab Flash notifications
                  if (this.flash_notifications) {
                    this.flashNotification();
                  }

                  // Sound notifications
                  if (
                    this.audio &&
                    ["a", "aa", "i"].includes(CHAT_SETTINGS["chat-sound"]) &&
                    (!this.chat_open || !this.tab_active || this.dashboard) &&
                    !(
                      SBF.null(message.message) && SBF.null(message.attachments)
                    )
                  ) {
                    this.audio.play();
                  }
                }
                if (is_new) {
                  SBF.event("SBNewConversationReceived", conversation);
                  if (tickets)
                    SBTickets.onNewConversationReceived(conversation);
                }
              }
              main
                .find(".sb-user-conversations")
                .html(activeUser().getConversationsCode());
              main
                .find(".sb-dashboard-conversations")
                .setClass(
                  "sb-conversations-hidden",
                  main.find(".sb-user-conversations > li").length > 3
                );
            }
          }
        );
      }
    },

    // Generate the conversation code and display it
    populate: function () {
      if (this.conversation) {
        let code = "";
        let notify = chat.find(" > .sb-notify-message");
        let last_date = false;
        for (var i = 0; i < this.conversation.messages.length; i++) {
          let message = this.conversation.messages[i];
          let current_date = SBF.beautifyTime(message.get("creation_time"));
          if (current_date != last_date) {
            code += `<div class="sb-label-date">${current_date}</div>`;
            last_date = current_date;
          }
          code += message.getCode();
        }
        chat.html((notify.length ? notify[0].outerHTML : "") + code);
        if (!this.dashboard) {
          this.scrollBottom();
          this.calculateLabelDates();
        }
      } else if (activeUser() && !activeUser().isConversationsEmpty()) {
        if (CHAT_SETTINGS["disable-dashboard"])
          this.openConversation(activeUser().conversations[0].id);
        else this.showDashboard();
      }
    },

    // Populate the dashboard with all conversations
    populateConversations: function (onSuccess = false) {
      if (!this.is_busy_populate && activeUser()) {
        this.is_busy_populate = true;
        setTimeout(() => {
          this.is_busy_populate = false;
        }, 5000);
        activeUser().getConversations((response) => {
          let count = response.length;

          if (count) {
            let now = Date.now();
            this.id_last_message = response[0]["messages"][0].id;
            for (var i = 0; i < count; i++) {
              if (
                response[i].get("conversation_status_code") == 1 &&
                (!this.conversation || this.conversation.id != response[i].id)
              ) {
                this.updateNotifications("add", response[i].id);
              }
              if (
                now - SBF.UTC(response[i]["messages"][0].get("creation_time")) <
                6000
              )
                this.open();
            }
            main.removeClass("sb-no-conversations");
            main
              .find(".sb-user-conversations")
              .html(activeUser().getConversationsCode());
            main
              .find(".sb-dashboard-conversations")
              .setClass(
                "sb-conversations-hidden",
                main.find(".sb-user-conversations > li").length > 3
              );
          }
          if (
            (!this.initialized || force_action == "open-conversation") &&
            count == 1 &&
            !this.isInitDashboard() &&
            !storage("open-conversation")
          ) {
            this.openConversation(activeUser().conversations[0].id);
            if (force_action == "open-conversation") force_action = "";
          }
          if (onSuccess) {
            onSuccess(response);
          }
          this.finalizeInit();
        });
      }
    },

    // Create a new conversation and optionally send the first message
    newConversation: function (
      status_code,
      user_id = -1,
      message = "",
      attachments = [],
      department = null,
      agent_id = null,
      onSuccess = false,
      source = selectedSource
    ) {
      if (activeUser()) {
        SBF.ajax(
          {
            function: "new-conversation",
            status_code: status_code,
            title: tickets ? main.find(".sb-ticket-title input").val() : null,
            department: SBF.null(department)
              ? this.default_department
              : department,
            agent_id: SBF.null(agent_id) ? this.default_agent : agent_id,
            tags: this.default_tags,
            source: source,
          },
          (response) => {
            if (SBF.errorValidation(response, "user-not-found")) {
              this.addUserAndLogin(() => {
                this.newConversation(
                  status_code,
                  user_id,
                  message,
                  attachments,
                  department,
                  agent_id,
                  onSuccess
                );
              });
              return;
            }
            let conversation = new SBConversation([], response["details"]);
            this.setConversation(conversation);
            this.sendMessage(user_id, message, attachments);
            // if (user_id != bot_id) {
            //   setTimeout(() => {
            //     this.queue(conversation.id);
            //   }, 1000);
            // }
            activeUser().conversations.push(conversation);
            if (onSuccess) onSuccess(conversation);
          }
        );
        // } else {
        //   SBF.error("activeUser() not setted", "SBChat.newConversation");
      }
    },

    // Set an existing conversation as active conversation
    setConversation: function (conversation) {
      if (conversation instanceof SBConversation) {
        this.conversation = conversation;
        this.id_last_message_conversation = !this.conversation.getLastMessage()
          ? 0
          : this.conversation.getLastMessage().id;
        this.datetime_last_message_conversation =
          this.conversation.getLastMessage() == false
            ? "2000-01-01 00:00:00"
            : this.conversation.getLastMessage().get("creation_time");
        if (conversation.id != this.conversation.id) {
          this.queue(conversation.id);
        }
        storage("open-conversation", conversation.id);
        SBF.event("SBActiveConversationChanged", conversation);
      } else {
        SBF.error("Value not of type SBConversation", "SBChat.setConversation");
      }
      //alert set get notes
      //SBF.startAlarm(SBChat.conversation.id)
    },

    // Manage all the queue functionalities 31/marzo
    // 		queue: function(conversation_id) {
    // 			if (conversation_id == 'clear') {
    // 				main.removeClass('sb-notify-active sb-queue-active');
    // 				chat.find(' > .sb-notify-message').remove();
    // 				clearInterval(this.queue_interval);
    // 				this.queue_interval = false;
    // 				storage('queue', '');
    // 				return;
    // 			}
    // 			if (!admin && CHAT_SETTINGS['queue']) {
    // 				SBF.ajax({
    // 					function: 'queue',
    // 					conversation_id: conversation_id,
    // 					department: this.conversation.get('department')
    // 				}, (response) => {
    // 					chat.find(' > .sb-notify-message').remove();
    // 					let position = response[0];
    // 					if (position == 0) {
    // 						this.queue('clear');
    // 					} else {
    // 						let time = (!CHAT_SETTINGS['queue-response-time'] ? 5 : parseInt(CHAT_SETTINGS['queue-response-time'])) * position;
    // 						let text = sb_(!CHAT_SETTINGS['queue-message'] ? 'Please wait for an agent. You are number {position} in the queue. Your waiting time is approximately {minutes} minutes.' : CHAT_SETTINGS['queue-message']).replace('{position}', '<b>' + position + '</b>').replace('{minutes}', '<b>' + time + '</b>');
    // 						if (response[1]) chat.prepend(`<div class="sb-notify-message sb-rich-cnt"><div class="sb-cnt"><div class="sb-message">${text}</div></div></div>`);
    // 						if (this.queue_interval === false) {
    // 							this.queue_interval = setInterval(() => {
    // 								this.queue(conversation_id)
    // 							}, 10100);
    // 							if (response[1]) main.addClass('sb-notify-active sb-queue-active');
    // 							storage('queue', conversation_id);
    // 						}
    // 					}
    // 					SBF.event('SBQueueUpdate', position);
    // 				});
    // 			}
    // 		},

    // Get the departments details and generate the department code
    getDepartmentCode(department_id, onSuccess) {
      if (this.departments) {
        if (department_id == "all") {
          let code = "";
          for (var key in this.departments) {
            this.getDepartmentCode(this.departments[key]["id"], (response) => {
              code += response;
            });
          }
          onSuccess(code);
        } else {
          onSuccess(
            `<div data-color="${this.departments[department_id]["color"]}">${
              this.departments[department_id]["image"] == ""
                ? `<span></span>`
                : `<img loading="lazy" src="${this.departments[department_id]["image"]}" />`
            }<div>${this.departments[department_id]["name"]}<div></div>`
          );
        }
      } else {
        SBF.ajax(
          {
            function: "get-departments",
          },
          (response) => {
            if (response) {
              this.departments = response;
              this.getDepartmentCode(department_id, onSuccess);
            }
          }
        );
      }
    },

    // Start and stop the real time check of new messages
    startRealTime: function () {
      if (SBPusher.active) return;
      this.stopRealTime();
      this.real_time = setInterval(() => {
        this.update();
        this.typing(
          admin ? (activeUser() ? activeUser().id : -1) : this.agent_id,
          "check"
        );
      }, 1000);
    },

    stopRealTime: function () {
      clearInterval(this.real_time);
    },

    // Check if the agent is online and set the online status of the active user
    updateUsersActivity: function () {
      if (activeUser()) {
        SBF.updateUsersActivity(activeUser().id, this.agent_id, (response) => {
          if (!this.typing_settings["typing"]) {
            if (response == "online" || this.agent_id == bot_id) {
              $(chat_status).addClass("sb-status-online").html(sb_("Online"));
              this.agent_online = this.agent_id != bot_id;
            } else {
              $(chat_status).removeClass("sb-status-online").html(sb_("Away"));
              this.agent_online = false;
            }
          }
        });
      }
    },

    // Show the loading icon and put the chat in busy mode
    busy: function (value) {
      chat_editor.find(".sb-loader").sbActive(value);
      this.is_busy = value;
      SBF.event("SBBusy", value);
    },

    // Manage the agent header
    headerAgent: function () {
      if (
        !admin &&
        !tickets &&
        !this.dashboard &&
        this.conversation &&
        (this.agent_id == -1 ||
          (this.conversation.getLastMessage() &&
            SBF.isAgent(this.conversation.getLastMessage().get("user_type")) &&
            this.conversation.getLastMessage().get("user_id") != this.agent_id))
      ) {
        let agent = this.lastAgent();
        if (agent) {
          this.agent_id = agent["user_id"];
          this.headerReset();
          chat_header
            .addClass("sb-header-agent")
            .attr("data-agent-id", this.agent_id)
            .html(
              `<div class="sb-dashboard-btn bi-chevron-left"></div><div class="sb-profile"><img loading="lazy" src="${
                agent["profile_image"]
              }" /><div><span class="sb-name">${
                agent["full_name"]
              }</span><span class="sb-status">${sb_(
                "Away"
              )}</span></div><i class="sb-icon bi-x-lg ${
                !mobile && CHAT_SETTINGS["close-chat"]
                  ? "sb-close-chat"
                  : "sb-responsive-close-btn"
              }"></i></div><div class="sb-label-date-top"></div>`
            );
          chat_status = chat_header.find(".sb-status");
          this.updateUsersActivity();
          label_date = chat_header.find(".sb-label-date-top");
          if (SBF.storageTime("header-animation", 1)) {
            this.headerAnimation();
          }
        }
      }
    },

    headerReset: function () {
      if (this.start_header == false) {
        this.start_header = [chat_header.html(), chat_header.attr("class")];
      }
      chat_header.removeClass(
        "sb-header-main sb-header-brand sb-header-agent sb-header-minimal"
      );
      this.main_header = false;
    },

    headerAnimation: function () {
      chat_header.addClass("sb-header-animation");
      setTimeout(() => {
        chat_header.removeClass("sb-header-animation");
      }, 8000);
      SBF.storageTime("header-animation");
    },

    // Return the last agent of the active conversation
    lastAgent: function (bot = true) {
      let agent = false;
      if (this.conversation) {
        let message = this.conversation.getLastUserMessage(
          false,
          bot ? "all" : true
        );
        if (message) {
          agent = {
            user_id: message.get("user_id"),
            full_name: message.get("full_name"),
            profile_image: message.get("profile_image"),
          };
        }
      }
      return agent;
    },

    // Scroll the chat to the bottom
    scrollBottom: function (top = false) {
      label_date_show = false;
      setTimeout(() => {
        label_date_show = true;
      }, 1000);
      // delay to scroll bottom on new messages
      setTimeout(() => {
        chat_scroll_area.scrollTop(top ? 0 : chat_scroll_area[0].scrollHeight);
        this.scrollHeader();
      }, 300);
    },

    // scrollBottom: function () {
    //   var scrollPosition = chat_scroll_area.scrollTop();
    //   var isScrolledBelowThreshold =
    //     chat_scroll_area[0].scrollHeight -
    //       scrollPosition -
    //       chat_scroll_area.innerHeight() <
    //     1000;

    //   if (isScrolledBelowThreshold) {
    //     label_date_show = false;
    //     setTimeout(() => {
    //       label_date_show = true;
    //     }, 1000);
    //     setTimeout(() => {
    //       chat_scroll_area.scrollTop(chat_scroll_area[0].scrollHeight);
    //       this.scrollHeader();
    //     }, 200);
    //   } else if (label_date_show !== true) {
    //     label_date_show = true;
    //   }
    // },

    // Check if the chat is at bottom
    isBottom: function () {
      return (
        chat_scroll_area[0].scrollTop ===
        chat_scroll_area[0].scrollHeight - chat_scroll_area[0].offsetHeight
      );
    },

    // Dashboard header animation
    scrollHeader: function () {
      if (this.main_header && this.dashboard) {
        let scroll = chat_scroll_area.scrollTop();
        if (scroll > -1 && scroll < 1000) {
          chat_header.find(".sb-content").css({
            opacity: 1 - scroll / 500,
            top: (scroll / 10) * -1 + "px",
          });
        }
      }
    },

    // Display the dashboard area
    showDashboard: function () {
      if (!admin && !tickets) {
        main.addClass("sb-dashboard-active");
        chat_header.removeClass("sb-header-agent");
        this.hidePanel();
        if (this.start_header)
          chat_header.html(this.start_header[0]).addClass(this.start_header[1]);
        chat_scroll_area.find(" > div").sbActive(false);
        main.find(".sb-dashboard").sbActive(true);
        this.populateConversations();
        this.conversation = false;
        this.agent_id = -1;
        this.stopRealTime();
        this.dashboard = true;
        this.main_header = true;
        this.scrollBottom(true);
        SBF.event("SBDashboard");
      }
    },

    // Hide the dashboard area
    hideDashboard: function () {
      if (!admin && !tickets) {
        chat.sbActive(true);
        main
          .removeClass("sb-dashboard-active")
          .find(".sb-dashboard")
          .sbActive(false);
        this.dashboard = false;
        this.headerAgent();
        this.scrollHeader(0);
        if (this.chat_open) {
          this.startRealTime();
        }
        SBF.event("SBDashboardClosed");
      }
    },

    // Show a chat panel
    showPanel: function (name, title) {
      if (tickets) return SBTickets.showPanel(name, title);
      let panel = chat_scroll_area.find(" > .sb-panel-" + name);
      if (panel.length) {
        chat_scroll_area.find(" > div").sbActive(false);
        panel.sbActive(true);
        if (!this.start_header)
          this.start_header = [chat_header.html(), chat_header.attr("class")];
        chat_header
          .attr("class", "sb-header sb-header-panel")
          .html(`${sb_(title)}<div class="sb-dashboard-btn bi-x-lg"></div>`);
        main.addClass("sb-panel-active");
        this.dashboard = true;
      }
      SBF.event("SBPanelActive", name);
    },

    hidePanel: function () {
      main.removeClass("sb-panel-active");
      chat_header.removeClass("sb-header-panel");
    },

    // Clear the conversation area and the active conversation
    clear: function () {
      this.conversation = false;
      chat.html("");
    },

    // Update the red notification counter of the chat
    updateNotifications: function (action = "add", conversation_id) {
      if (action == "add" && !this.notifications.includes(conversation_id)) {
        this.notifications.push(conversation_id);
        if (
          !this.dashboard &&
          this.conversation &&
          this.conversation.id != conversation_id
        )
          this.headerAnimation();
      }
      if (action == "remove") {
        for (var i = 0; i < this.notifications.length; i++) {
          if (this.notifications[i] == conversation_id) {
            this.notifications.splice(i, 1);
            if (
              this.conversation.get("conversation_status_code") != 0 &&
              ["1", "2", 1, 2].includes(
                this.conversation.get("conversation_status_code")
              )
            ) {
              this.setConversationStatus(0);
            }
            break;
          }
        }
      }
      let count = this.notifications.length;
      main
        .find(".sb-chat-btn span")
        .attr("data-count", count)
        .html(count > -1 ? count : 0);
      SBF.event("SBNotificationsUpdate", {
        action: action,
        conversation_id: conversation_id,
      });
    },

    // Set the active conversation status
    setConversationStatus: function (status_code) {
      if (this.conversation) {
        SBF.ajax(
          {
            function: "update-conversation-status",
            conversation_id: this.conversation.id,
            status_code: status_code,
          },
          () => {
            this.conversation.set("conversation_status_code", status_code);
            SBF.event("SBActiveConversationStatusUpdated", {
              conversation_id: this.conversation.id,
              status_code: status_code,
            });
          }
        );
        return true;
      }
      return false;
    },

    // Typing status
    typing: function (user_id = -1, action = "check") {
      if (this.conversation) {
        let valid = this.agent_online || (admin && this.user_online);
        if (
          action == "check" &&
          !SBPusher.active &&
          user_id != -1 &&
          user_id != bot_id &&
          valid
        ) {
          SBF.ajax(
            {
              function: "is-typing",
              user_id: user_id,
              conversation_id: this.conversation.id,
            },
            (response) => {
              if (response && !this.typing_settings["typing"]) {
                this.typing(-1, "start");
              } else if (!response && this.typing_settings["typing"]) {
                this.typing(-1, "stop");
              }
            }
          );
        } else if (action == "set" && valid) {
          let source = this.conversation.get("source");
          if (source) {
            source =
              source == "fb"
                ? [
                    source,
                    activeUser().getExtra("facebook-id")["value"],
                    this.conversation.get("extra"),
                  ]
                : source == "tw"
                ? [source, activeUser().getExtra("twitter-id")["value"]]
                : false;
          }
          if (SBPusher.active) {
            SBF.debounce(() => {
              SBPusher.trigger("client-typing", {
                user_id: admin ? SB_ACTIVE_AGENT["id"] : activeUser().id,
                conversation_id: this.conversation.id,
              });
              if (source)
                SBF.ajax({
                  function: "set-typing",
                  source: source,
                });
            }, "#2");
          } else {
            if (!this.typing_settings["sent"]) {
              this.typing_settings["sent"] = true;
              SBF.ajax({
                function: "set-typing",
                user_id: user_id,
                conversation_id: this.conversation.id,
                source: source,
              });
              this.typing(user_id, "set");
            } else {
              clearTimeout(this.typing_settings["timeout"]);
              this.typing_settings["timeout"] = setTimeout(() => {
                SBF.ajax(
                  {
                    function: "set-typing",
                    user_id: user_id,
                    conversation_id: -1,
                  },
                  () => {
                    this.typing_settings["sent"] = false;
                  }
                );
              }, 2000);
            }
          }
        } else if (action == "start" || action == "stop") {
          let start = action == "start";
          if (!admin && chat_status) {
            if (start) {
              $(chat_status).addClass("sb-status-typing").html(sb_("Typing"));
            } else {
              let online = this.agent_online || this.agent_id == bot_id;
              $(chat_status)
                .removeClass("sb-status-typing")
                .html(sb_(online ? "Online" : "Away"));
              if (online) $(chat_status).addClass("sb-status-online");
            }
          }
          this.typing_settings["typing"] = start;
          SBF.event("SBTyping", start);
        }
      }
    },

    // Emoji
    categoryEmoji: function (category) {
      let list = this.emoji_options["list"];
      if (category == "all") {
        this.emoji_options["list_now"] = list;
      } else {
        this.emoji_options["list_now"] = [];
        for (var i = 0; i < list.length; i++) {
          if (list[i]["category"].startsWith(category)) {
            this.emoji_options["list_now"].push(list[i]);
          }
        }
      }
      this.emoji_options["range"] = 0;
      this.populateEmoji(0);
      this.populateEmojiBar();
    },

    mouseWheelEmoji: function (e) {
      let range = this.emoji_options["range"];
      if (
        sbDelta(e) > 0 ||
        (mobile &&
          typeof e.originalEvent.changedTouches !== ND &&
          this.emoji_options["touch"] <
            e.originalEvent.changedTouches[0].clientY)
      ) {
        range -= range < 1 ? 0 : 1;
      } else {
        range += range > this.emoji_options["range_limit"] ? 0 : 1;
      }
      chat_emoji
        .find(".sb-emoji-bar > div")
        .sbActive(false)
        .eq(range)
        .sbActive(true);
      this.emoji_options["range"] = range;
      this.populateEmoji(range);
      e.preventDefault();
    },

    insertEmoji: function (emoji) {
      if (emoji.indexOf(".svg") > 0) {
        emoji = $.parseHTML(emoji)[0]["alt"];
      }
      this.insertText(emoji);
      chat_emoji.sbTogglePopup();
    },

    showEmoji: function (button) {
      if (chat_emoji.sbTogglePopup(button)) {
        if (!admin) {
          chat_emoji.css({
            left: chat_editor.offset().left + (tickets ? 68 : 20),
            top:
              chat_editor.offset().top -
              window.scrollY -
              (tickets ? chat_editor.height() - 330 : 304),
          });
        }
        if (chat_emoji.find(".sb-emoji-list > ul").html() == "") {
          jQuery
            .ajax({
              method: "POST",
              url: SB_AJAX_URL,
              data: {
                function: "emoji",
              },
            })
            .done((response) => {
              this.emoji_options["list"] = JSON.parse(response);
              this.emoji_options["list_now"] = this.emoji_options["list"];
              this.populateEmoji(0);
              this.populateEmojiBar();
            });
        }
        SBF.deselectAll();
      }
    },

    populateEmoji: function (range) {
      let code = "";
      let per_page = mobile ? 42 : 48;
      let limit = range * per_page + per_page;
      let list_now = this.emoji_options["list_now"];
      if (limit > list_now.length) limit = list_now.length;
      this.emoji_options["range_limit"] = list_now.length / per_page - 1;
      this.emoji_options["range"] = range;
      for (var i = range * per_page; i < limit; i++) {
        code += `<li>${list_now[i]["char"]}</li>`;
      }
      chat_emoji.find(".sb-emoji-list").html(`<ul>${code}</ul>`);
    },

    populateEmojiBar: function () {
      let code = '<div class="sb-active"></div>';
      let per_page = mobile ? 42 : 49;
      for (
        var i = 0;
        i < this.emoji_options["list_now"].length / per_page - 1;
        i++
      ) {
        code += "<div></div>";
      }
      this.emoji_options["range"] = 0;
      chat_emoji.find(".sb-emoji-bar").html(code);
    },

    clickEmojiBar: function (item) {
      let range = $(item).index();
      this.populateEmoji(range);
      this.emoji_options["range"] = range;
      chat_emoji
        .find(".sb-emoji-bar > div")
        .sbActive(false)
        .eq(range)
        .sbActive(true);
    },

    searchEmoji: function (search) {
      SBF.search(search, () => {
        if (search.length > 1) {
          let list = this.emoji_options["list"];
          let list_now = [];
          for (var i = 0; i < list.length; i++) {
            if (
              list[i]["category"].toLowerCase().includes(search) ||
              list[i]["name"].toLowerCase().includes(search)
            ) {
              list_now.push(list[i]);
            }
          }
          this.emoji_options["list_now"] = list_now;
        } else {
          this.emoji_options["list_now"] = this.emoji_options["list"];
        }
        this.emoji_options["range"] = 0;
        this.populateEmoji(0);
        this.populateEmojiBar();
      });
    },

    // Editor methods
    textareaChange: function (textarea) {
      let value = $(textarea).val();

      // Saved replies
      if (admin) {
        SBAdmin.conversations.savedReplies(textarea, value);
      }

      // Typing
      if (value) {
        this.typing(
          admin && !SBPusher.active ? SB_ACTIVE_AGENT["id"] : activeUser().id,
          "set"
        );
        this.activateBar();
      } else {
        this.activateBar(false);
      }
    },

    insertText: function (text) {
      let textarea = $(chat_textarea.get(0));
      let index = 0;
      if (this.dashboard) return false;
      if ("selectionStart" in textarea.get(0)) {
        index = textarea.get(0).selectionStart;
      } else if ("selection" in document) {
        textarea.focus();
        let selection = document.selection.createRange();
        var selection_length = document.selection.createRange().text.length;
        selection.moveStart("character", -textarea.value.length);
        index = selection.text.length - selection_length;
      }
      textarea.val(
        textarea.val().substr(0, index) + text + textarea.val().substr(index)
      );
      textarea.focus();
      textarea.manualExpandTextarea();
      this.activateBar();
    },

    enabledAutoExpand: function () {
      if (chat_textarea.length) chat_textarea.autoExpandTextarea();
    },

    // Privacy message
    privacy: function () {
      SBF.ajax(
        {
          function: "get-block-setting",
          value: "privacy",
        },
        (response) => {
          chat_scroll_area.append(
            `<div class="sb-privacy sb-init-form" data-decline="${response["decline"]}"><div class="sb-title">${response["title"]}</div><div class="sb-text">${response["message"]}</div>` +
              (response["link"]
                ? `<a target="_blank" href="${response["link"]}">${response["link-name"]}</a>`
                : "") +
              `<div class="sb-buttons"><a class="sb-btn sb-approve">${response["btn-approve"]}</a><a class="sb-btn sb-decline">${response["btn-decline"]}</a></div></div>`
          );
          this.finalizeInit();
          SBF.event("SBPrivacy");
        }
      );
      if (!this.dashboard) this.showDashboard();
      this.dashboard = true;
      main.addClass("sb-init-form-active");
    },

    // Popup message
    popup: function (close = false, content = false) {
      if (close) {
        let popup = main.find(".sb-popup-message");
        let id = popup.attr("data-id");
        storage("popup" + (SBF.null(id) ? "" : id), true);
        popup.remove();
        return;
      }
      setTimeout(() => {
        if (!this.chat_open) {
          if (content == false) content = CHAT_SETTINGS["popup"];
          main.find(".sb-popup-message").remove();
          main.append(
            `<div data-id="${
              "id" in content ? content["id"] : ""
            }" class="sb-popup-message">` +
              ("image" in content && content["image"]
                ? `<img loading="lazy" src="${content["image"]}" />`
                : "") +
              ("title" in content && content["title"]
                ? `<div class="sb-top">${content["title"]}</div>`
                : "") +
              `<div class="sb-text">${content["message"]}</div><div class="bi-x-lg"></div></div>`
          );
          SBF.event("SBPopup", content);
        }
      }, 1000);
    },

    // Follow up message
    followUp: function () {
      if (this.followUpCheck()) {
        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(
          () => {
            if (this.followUpCheck()) {
              let settings = CHAT_SETTINGS["follow"];
              this.sendMessage(
                bot_id,
                `[email id="sb-follow-up-form" title="${settings["title"]}" message="${settings["message"]}" placeholder="${settings["placeholder"]}" name="${settings["name"]}" last-name="${settings["last-name"]}" phone="${settings["phone"]}" phone-required="${settings["phone-required"]}" success="${settings["success"]}"]`
              );
              this.scrollBottom();
              SBF.storageTime("email");
              SBF.event("SBFollowUp");
            }
          },
          SBF.null(CHAT_SETTINGS["follow"]["delay"])
            ? CHAT_SETTINGS["office-hours"] || agents_online
              ? 15000
              : SBApps.dialogflow.active()
              ? 8000
              : 5000
            : parseInt(CHAT_SETTINGS["follow"]["delay"])
        );
      }
    },

    followUpCheck: function () {
      return (
        !admin &&
        this.conversation &&
        CHAT_SETTINGS["follow"]["active"] &&
        activeUser() &&
        !activeUser().get("email") &&
        SBF.storageTime("email", 24) &&
        (CHAT_SETTINGS["office-hours"] ||
          !CHAT_SETTINGS["follow"]["disable-office-hours"])
      );
    },

    // Welcome message
    welcome: function () {
      if (
        (CHAT_SETTINGS["welcome-trigger"] != "open" || this.chat_open) &&
        (CHAT_SETTINGS["office-hours"] ||
          !CHAT_SETTINGS["welcome-disable-office-hours"]) &&
        CHAT_SETTINGS["welcome"] &&
        !storage("welcome") &&
        activeUser()
      ) {
        SBF.ajax(
          {
            function: "get-block-setting",
            value: "welcome",
          },
          (response) => {
            setTimeout(() => {
              if (CHAT_SETTINGS["dialogflow-welcome"]) {
                if (this.conversation === false) {
                  this.newConversation(3, -1, "", [], null, null, function () {
                    SBApps.dialogflow.welcome(
                      response["open"],
                      response["sound"]
                    );
                  });
                } else {
                  SBApps.dialogflow.welcome(
                    response["open"],
                    response["sound"]
                  );
                }
              }
              {
                this.sendMessage(
                  bot_id,
                  response["message"],
                  [],
                  false,
                  false,
                  3
                );
                if (response["open"]) {
                  this.start();
                }
                if (response["sound"]) {
                  this.audio.play();
                }
              }
              this.skip = true;
              SBF.event("SBWelcomeMessage");
            }, parseInt(tickets ? 0 : CHAT_SETTINGS["welcome-delay"]));
            storage("welcome", true);
          }
        );
      }
    },

    // Subscribe message
    // subscribe: function () {
    //   if (
    //     CHAT_SETTINGS["subscribe"] &&
    //     !storage("subscribe") &&
    //     activeUser() &&
    //     SBF.null(activeUser().get("email")) &&
    //     activeUser()
    //   ) {
    //     setTimeout(() => {
    //       SBF.ajax(
    //         {
    //           function: "get-block-setting",
    //           value: "subscribe",
    //         },
    //         (response) => {
    //           this.sendMessage(
    //             bot_id,
    //             response["message"],
    //             [],
    //             false,
    //             {
    //               event: "open-chat",
    //             },
    //             3
    //           );
    //           if (response["sound"] && this.audio) {
    //             this.audio.play();
    //           }
    //           this.skip = true;
    //           storage("subscribe", true);
    //         }
    //       );
    //     }, parseInt(CHAT_SETTINGS["subscribe-delay"]));
    //   }
    // },

    // Offline timetable message
    offlineMessage: function () {
      if (
        !admin &&
        !this.offline_message_set &&
        CHAT_SETTINGS["timetable"] &&
        (!CHAT_SETTINGS["office-hours"] ||
          (!agents_online && !CHAT_SETTINGS["timetable-disable-agents"])) &&
        SBF.storageTime("timetable", 1)
      ) {
        let message = CHAT_SETTINGS["timetable-message"];
        switch (CHAT_SETTINGS["timetable-type"]) {
          case "header":
            if (message[0]) chat_header.find(".sb-title").html(message[0]);
            chat_header.find(".sb-text").html(message[1]);
            this.offline_message_set = true;
            break;
          case "info":
            chat.prepend(
              `<div class="sb-notify-message sb-rich-cnt">
              <div class="server-response"> <i class="bi-check2-all"></i></div>
              <div class="sb-cnt"><div class="sb-message">${
                message[0] ? `<strong>${message[0]}</strong> ` : ""
              }${message[1]}</div></div></div>`
            );
            main.addClass("sb-notify-active");
            this.offline_message_set = true;
            break;
          default:
            setTimeout(() => {
              if (this.conversation) {
                this.sendMessage(
                  bot_id,
                  CHAT_SETTINGS["timetable-hide"]
                    ? `${message[0] ? `*${message[0]}*\n` : ""}${message[1]}`
                    : "[timetable]"
                );
                this.scrollBottom();
                this.offline_message_set = true;
                SBF.storageTime("timetable");
              }
            }, 5000);
        }
      }
    },

    // Delete message
    deleteMessage: function (message_id) {
      SBF.ajax(
        {
          function: "delete-message",
          message_id: message_id,
        },
        () => {
          if (this.conversation) this.conversation.deleteMessage(message_id);
          chat.find(`[data-id="${message_id}"]`).remove();
          this.update();
          console.log("chat", message_id);
          SBF.event("SBMessageDeleted", message_id);
        }
      );
    },

    // Registration form
    registration: function (
      check = false,
      type = CHAT_SETTINGS["registration-required"]
    ) {
      if (check)
        return (
          CHAT_SETTINGS["registration-required"] &&
          (!CHAT_SETTINGS["registration-offline"] || !agents_online) &&
          (!CHAT_SETTINGS["registration-timetable"] ||
            !CHAT_SETTINGS["office-hours"]) &&
          (activeUser() === false ||
            ["visitor", "lead"].includes(activeUser().type))
        );
      chat_scroll_area.append(
        SBRichMessages.generate(
          {},
          CHAT_SETTINGS["registration-link"] ? "login" : type,
          "sb-init-form"
        )
      );
      if (!this.dashboard) this.showDashboard();
      this.dashboard = true;
      this.finalizeInit();
      main.addClass("sb-init-form-active");
    },

    // Display the send button
    activateBar: function (show = true) {
      chat_editor.find(".sb-bar").sbActive(show);
      if (show) {
        chat_editor.find(".bi-mic-fill").hide();
      } else {
        chat_editor.find(".bi-mic-fill").show();
      }
    },


    // Shortcut for add user and login function
    addUserAndLogin: function (onSuccess = false, lead = false) {
      let settings = typeof SB_DEFAULT_USER != ND ? SB_DEFAULT_USER : {};
      settings.user_type = lead ? "lead" : "visitor";
      SBF.ajax(
        {
          function: "add-user-and-login",
          settings: settings,
          settings_extra: settings.extra,
        },
        (response) => {
          SBF.loginCookie(response[1]);
          activeUser(new SBUser(response[0]));
          SBPusher.start();
          if (!SBPusher.active) SBChat.automations.run_all();
          if (onSuccess) {
            onSuccess(response);
          }
        }
      );
    },

    // Check if the dashboard must be showed
    isInitDashboard: function () {
      return (
        CHAT_SETTINGS["init-dashboard"] ||
        (activeUser() && activeUser().conversations.length > 1)
      );
    },

    // Upload response
    uploadResponse: function (response) {
      response = JSON.parse(response);
      if (response[0] == "success") {
        if (response[1] == "extension_error") {
          let message =
            "The file you are trying to upload has an extension that is not allowed.";
          if (admin) SBAdmin.dialog(message, "info");
          else alert(message);
        } else if ($(upload_target).hasClass("sb-input-image")) {
          $(upload_target)
            .find(".image")
            .attr("data-value", "")
            .css("background-image", "");
          setTimeout(() => {
            $(upload_target)
              .find(".image")
              .attr("data-value", response[1])
              .css(
                "background-image",
                `url("${response[1]}?v=${SBF.random()}")`
              )
              .append('<i class="bi-x-lg"></i>');
            upload_target = false;
          }, 500);
        } else {
          let name = response[1].substr(response[1].lastIndexOf("/") + 1);
          let attachmentElement = "";

          // Check if response[1] is a valid image URL
          const isImageURL = /\.(jpg|jpeg|png|)$/.test(
            response[1].toLowerCase()
          );

          // Check if response[1] is a valid audio URL
          const isAudioURL = /\.(mp3|ogg)$/.test(response[1].toLowerCase());

          // Check if response[1] is a valid Office format URL
          const isOfficeURL = /\.(docx|pptx|xlsx|doc|ppt|xls)$/.test(
            response[1].toLowerCase()
          );

          // Conditionally construct HTML
          if (isImageURL) {
            attachmentElement = `<div data-name="${name}" data-value="${response[1]}" data-id="${response[2]}">
                                <img style="border-radius: .4rem; width: 33px; vertical-align: middle;object-fit:cover;" src="${response[1]}" width="30" height="30">
                                <i class="bi-x-lg"></i>
                              </div>`;
          } else if (isAudioURL) {
            attachmentElement = `<div style="display:flex;flex-direction:row;align-items: center;" data-name="${name}" data-value="${
              response[1]
            }" data-id="${response[2]}">
                                <audio controls style="max-width: 100%; max-height: 33px; border-radius: var(--chat-rounded-size-6);">
                                  <source src="${
                                    response[1]
                                  }" type="audio/${response[1]
              .split(".")
              .pop()}">
                                  Your browser does not support the audio element.
                                </audio>
                                <i class="bi-x-lg"></i>
                              </div>`;
          } else if (isOfficeURL) {
            // For Office formats, use doc.png as a placeholder image
            attachmentElement = `<div data-name="${name}" data-value="${response[1]}" data-id="${response[2]}">
                                <i class="bi bi-file-earmark-text-fill" style="font-size:1.8rem"></i>
                                 <i class="bi-x-lg"></i>
                              </div>`;
          } else {
            attachmentElement = `<div data-name="${name}" data-value="${response[1]}" data-id="${response[2]}">
          <i class="bi bi-file-earmark-text-fill" style="font-size:1.89rem; color: var(--chat-text-primary)"></i>
           <i class="bi-x-lg"></i>
        </div`;
          }

          chat_editor.find(".sb-attachments").append(attachmentElement);
          SBChat.activateBar();
        }
      } else {
        SBF.error(response[1], "sb-upload-files.change");
      }
      this.busy(false);
    },

    // Archive a conversation and close it
    closeChat: function (ajax = true) {
      let id = this.conversation.id;
      SBChat.clear();
      if (ajax) {
        SBF.ajax(
          {
            function: "update-conversation-status",
            conversation_id: id,
            status_code: 3,
          },
          () => {
            close();
          }
        );
      } else {
        close();
      }

      function close() {
        main.find(`li[data-conversation-id="${id}"]`).remove();
        force_action = "new-conversation";
        SBChat.clear();
        storage("open-conversation", "");
        activeUser().removeConversation(id);
        if (!CHAT_SETTINGS["disable-dashboard"]) {
          SBChat.showDashboard();
        }
      }
    },

    // Automations
    automations: {
      history: [],
      busy: [],
      scroll_position_intervals: {},
      timeout_queue: [],

      run_all: function () {
        let automations = CHAT_SETTINGS["automations"];
        for (var i = 0; i < automations.length; i++) {
          let automation = automations[i];
          let conditions = automation.conditions;
          let count = conditions.length;
          let valid = count == 0;
          let browsing_time = false;
          let scroll_position = false;
          let server_conditions = false;
          for (var j = 0; j < conditions.length; j++) {
            let criteria = conditions[j][1];
            valid = false;
            switch (conditions[j][0]) {
              case "browsing_time":
                valid = true;
                browsing_time = criteria;
                break;
              case "scroll_position":
                valid = true;
                scroll_position = criteria;
                break;
              case "include_urls":
              case "exclude_urls":
              case "referring":
                let url =
                  conditions[j][0] == "referring"
                    ? document.referrer
                    : window.location.href;
                let checks = conditions[j][2].split(",");
                let include = conditions[j][0] != "exclude_urls";
                if (!include) valid = true;
                url = url
                  .replace("https://", "")
                  .replace("http://", "")
                  .replace("www.", "");
                for (var y = 0; y < checks.length; y++) {
                  checks[y] = $.trim(
                    checks[y]
                      .replace("https://", "")
                      .replace("http://", "")
                      .replace("www.", "")
                  );
                  if (
                    (criteria == "contains" && url.indexOf(checks[y]) != -1) ||
                    (criteria == "does-not-contain" &&
                      url.indexOf(checks[y]) == -1) ||
                    (criteria == "is-exactly" && checks[y] == url) ||
                    (criteria == "is-not" && checks[y] != url)
                  ) {
                    valid = include;
                    break;
                  }
                }
                break;
              case "custom_variable":
                let variable = criteria.split("=");
                if (
                  variable[0] in window &&
                  window[variable[0]] == variable[1]
                ) {
                  valid = true;
                }
                break;
              case "returning_visitor":
              case "user_type":
              case "cities":
              case "languages":
              case "countries":
                valid = activeUser();
                server_conditions = true;
                break;
              case "user_phone":
                valid =
                  activeUser() && !SBF.null(activeUser().getExtra("phone"));
                break;
              case "user_email":
                valid = activeUser() && !SBF.null(activeUser().get("email"));
                break;
              default:
                valid = true;
            }
            if (!valid) break;
          }
          if (
            ["messages", "emails", "sms"].includes(automation.type) &&
            !activeUser()
          )
            valid = false;
          if (valid) {
            if (server_conditions) {
              if (!(automation.id in this.busy)) {
                SBF.ajax(
                  {
                    function: "automations-validate",
                    automation: automation,
                  },
                  (response) => {
                    if (response !== false) {
                      this.run_all_final(
                        automation,
                        scroll_position,
                        browsing_time
                      );
                    }
                    delete this.busy[automation.id];
                  }
                );
                this.busy[automation.id] = true;
              }
            } else {
              this.run_all_final(automation, scroll_position, browsing_time);
            }
          }
        }
      },

      run_all_final: function (automation, scroll_position, browsing_time) {
        if (scroll_position) {
          this.scroll_position_intervals[automation.id] = setInterval(() => {
            if ($(window).scrollTop() > parseInt(scroll_position)) {
              if (browsing_time)
                setTimeout(() => {
                  this.run(automation);
                }, parseInt(browsing_time) * 1000);
              else this.run(automation);
              clearInterval(this.scroll_position_intervals[automation.id]);
            }
          }, 1000);
        } else if (browsing_time) {
          if (!this.timeout_queue.includes(automation.id)) {
            setTimeout(() => {
              this.run(automation);
            }, parseInt(browsing_time) * 1000);
            this.timeout_queue.push(automation.id);
          }
        } else this.run(automation);
      },

      run: function (automation) {
        if (this.history.includes(automation.id)) return;
        switch (automation.type) {
          case "messages":
          case "emails":
          case "sms":
            if (
              (!SBPusher.active || SBPusher.started) &&
              !(automation.id in this.busy)
            ) {
              if (automation.type == "messages" && SBChat.chat_open) {
                let last_message = SBChat.conversation
                  ? SBChat.conversation.getLastUserMessage(false, "no-bot")
                  : false;
                if (
                  last_message &&
                  Date.now() - 600000 <
                    SBF.unix(last_message.get("creation_time"))
                )
                  return;
              }
              SBF.ajax(
                {
                  function: "automations-run",
                  automation: automation,
                },
                (response) => {
                  if (response !== false) {
                    this.history.push(automation.id);
                    if (automation.type == "messages" && !SBPusher.active)
                      SBChat.updateConversations();
                  }
                  delete this.busy[automation.id];
                }
              );
              this.busy[automation.id] = true;
            }
            break;
          case "popups":
            if (!storage("popup" + automation.id)) {
              setTimeout(() => {
                if (!SBChat.chat_open) {
                  SBChat.popup(false, {
                    id: automation.id,
                    image: automation.profile_image,
                    title: automation.title,
                    message: automation.message,
                  });
                  this.history.push(automation.id);
                } else if (automation.fallback) {
                  let last_message = SBChat.conversation
                    ? SBChat.conversation.getLastUserMessage(false, "no-bot")
                    : false;
                  if (
                    !last_message ||
                    Date.now() - 600000 >
                      SBF.unix(last_message.get("creation_time"))
                  ) {
                    SBChat.sendMessage(
                      bot_id,
                      (SBF.null(automation.title)
                        ? ""
                        : `*${automation.title}*\n`) + automation.message,
                      [],
                      false,
                      false,
                      0
                    );
                    storage("popup" + automation.id, true);
                    this.history.push(automation.id);
                  }
                }
              }, 1000);
            }
            break;
          case "design":
            if (automation.background) {
              chat_header.css(
                "background-image",
                `url("${automation.background}")`
              );
            }
            if (automation.brand) {
              chat_header.find(".sb-brand img").attr("src", automation.brand);
            }
            if (automation.title) {
              chat_header.find(".sb-title").html(automation.title);
            }
            if (automation.message) {
              chat_header.find(".sb-text").html(automation.message);
            }
            if (automation.icon) {
              main.find(".sb-chat-btn .sb-icon").attr("src", automation.icon);
            }
            if (
              automation.color_1 ||
              automation.color_2 ||
              automation.color_3
            ) {
              SBF.ajax(
                {
                  function: "chat-css",
                  color_1: automation.color_1,
                  color_2: automation.color_2,
                  color_3: automation.color_3,
                },
                (response) => {
                  global.append(`<style>${response}</style>`);
                }
              );
            }
            this.history.push(automation.id);
            break;
          case "more":
            let parameters = {};
            if (automation.department) {
              SBChat.default_department = automation.department;
              parameters = {
                function: "update-conversation-department",
                department: automation.department,
              };
            }
            if (automation.agent) {
              SBChat.default_agent = automation.agent;
              parameters = {
                function: "update-conversation-agent",
                agent_id: automation.agent,
              };
            }
            if (automation.tags) {
              automation.tags = automation.tags.split(",");
              SBChat.default_tags = automation.tags;
              parameters = {
                function: "update-tags",
                tags: automation.tags,
                add: true,
              };
            }
            if (
              SBChat.conversation.id &&
              (automation.tags || automation.agent || automation.department)
            ) {
              parameters.conversation_id = SBChat.conversation.id;
              SBF.ajax(parameters);
            }
            break;
        }
      },
    },

    // More
    flashNotification: function () {
      clearInterval(interval);
      interval = setInterval(function () {
        document.title =
          document.title == document_title
            ? sb_("New message...")
            : document_title;
      }, 2000);
    },

    calculateLabelDates: function () {
      if (admin || this.chat_open)
        label_date_items = chat.find(".sb-label-date");
    },

    calculateLabelDateFirst: function () {
      if (!this.conversation.messages.length)
        chat.append(
          `<div class="sb-label-date"><span>${sb_("Today")}</span></div>`
        );
    },
  };
  window.SBChat = SBChat;

  /*
   * ----------------------------------------------------------
   * # RICH MESSAGES
   * ----------------------------------------------------------
   */

  var SBRichMessages = {
    rich_messsages: {
      email: "",
      button: "",
      video: "",
      si: '<div class="sb-btn">Sí</div>',
      no: '<div class="sb-btn">No</div>',
      image: "",
      chips: '<div style="margin: 8px;" class="sb-buttons">[options]</div>',
      buttons: '<div class="sb-buttons">[options]</div>',
      select: '<div class="sb-select"><p></p><ul>[options]</ul></div>',
      list: '<div class="sb-text-list">[values]</div>',
      "list-image": '<div class="sb-image-list">[values]</div>',
      table: "<table><tbody>[header][values]</tbody></table>",
      inputs: '<div class="sb-form">[values]</div>',
      rating: `<div class="sb-rating"><span>[label]</span></div>`,
      card: '<div class="sb-card">[settings]</div>',
      share: '<div class="sb-social-buttons">[settings]</div>',
      slider:
        '<div class="sb-slider"><div>[items]</div></div><div class="sb-slider-arrow bi-chevron-left[class]"></div><div class="sb-slider-arrow bi-chevron-right sb-active[class]"></div>',
      "slider-images":
        '<div class="sb-slider sb-slider-images"><div>[items]</div></div><div class="sb-slider-arrow bi-chevron-left[class]"></div><div class="sb-slider-arrow bi-chevron-right sb-active[class]"></div>',
      phone: "",
    },
    cache: {},

    generate: function (settings, name, css = "") {
      let content;
      let next = true;
      let id = "id" in settings ? settings["id"] : SBF.random();
      let render = new SBMessage({});

      // Check if the rich message exist
      if (name in this.rich_messsages) {
        content = this.rich_messsages[name];
      } else if (name in this.cache) {
        content = this.cache[name];
      } else if (
        admin ||
        (!SBF.null(CHAT_SETTINGS) &&
          "rich-messages" in CHAT_SETTINGS &&
          CHAT_SETTINGS["rich-messages"].includes(name))
      ) {
        if (!("id" in settings)) id = name;
        content = '<div class="sb-rich-loading sb-loading"></div>';
        SBF.ajax(
          {
            function: "get-rich-message",
            name: name,
            settings: settings,
          },
          (response) => {
            response = render.render(this.initInputs(response));
            if (name == "timetable") response = this.timetable(response);
            main
              .find(`.sb-rich-message[id="${id}"]`)
              .html(`<div class="sb-content">${response}</div>`);
            this.cache[name] = response;
            SBChat.scrollBottom(SBChat.dashboard);
          }
        );
        next = false;
      } else {
        content = `[${name}]`;
      }

      // Generate the rich message
      let disabled = "disabled" in settings;
      if (next) {
        let options;
        let code = "";
        let rate = SBF.get_value(
          SBF.admin_set("rate-and-review")["rate-review"]
        );
        switch (name) {
          case "button":
            content =
              settings.link && settings.style
                ? `<a href="${settings.link
                    .replace(/<i>/g, "_")
                    .replace(/<\/i>/g, "_")}"${
                    "target" in settings ? ' target="_blank"' : ""
                  } class="sb-rich-btn sb-btn${
                    settings.style == "link" ? "-text" : ""
                  }">${sb_(settings.name)}</a>`
                : settings.yes && settings.no
                ? settings.yes === "si" && settings.no === "no"
                  ? `${SBRichMessages.rich_messsages.yes}${SBRichMessages.rich_messsages.no}`
                  : settings.yes === "yes" && settings.no === "no"
                  ? `${SBRichMessages.rich_messsages.no}${SBRichMessages.rich_messsages.yes}`
                  : `<button class="sb-rich-btn sb-btn">${sb_(
                      settings.name
                    )}</button>`
                : `<button class="sb-rich-btn sb-btn">${sb_(
                    settings.name
                  )}</button>`;
            break;

          case "rating":
            content = content
              .replace(
                "[label]",
                sb_(
                  SBF.null(settings["label"]) ? sb_(rate) : settings["label"]
                ).replace(/\n/g, "<br>")
              )
              .replace(
                "[success-negative]",
                SBF.null(settings["success-negative"])
                  ? ""
                  : sb_(settings["success-negative"])
              )
              .replace(
                "[positive]",
                sb_(
                  SBF.null(settings["label-positive"])
                    ? "Helpful"
                    : settings["label-positive"]
                )
              )
              .replace(
                "[negative]",
                sb_(
                  SBF.null(settings["label-negative"])
                    ? "Not helpful"
                    : settings["label-negative"]
                )
              );
            break;

          case "email":
            let inputs = [];
            let email = activeUser().get("email");
            let default_name = activeUser().get("last_name").charAt(0) == "#";
            if (settings["name"] == "true")
              inputs.push([
                "first_name",
                settings["last-name"] == "true" ? "First name" : "Name",
                default_name
                  ? ""
                  : settings["last-name"] == "true"
                  ? activeUser().get("first_name")
                  : activeUser().name,
                "text",
                true,
              ]);
            if (settings["last-name"] == "true")
              inputs.push([
                "last_name",
                "Last name",
                default_name ? "" : activeUser().get("last_name"),
                "text",
                true,
              ]);
            for (var i = 0; i < inputs.length; i++) {
              content += `<div id="${
                inputs[i][0]
              }" data-type="text" class="sb-input sb-input-text"><span class="${
                inputs[i][2] == "" ? "" : "sb-active sb-filled"
              }">${sb_(inputs[i][1])}</span><input value="${
                inputs[i][2]
              }" autocomplete="false" type="${inputs[i][3]}" ${
                inputs[i][4] ? "required" : ""
              }></div>`;
            }
            if (settings["phone"] == "true") {
              let phone = activeUser().getExtra("phone");
              let phone_codes = admin ? [] : CHAT_SETTINGS["phone-codes"];
              let select = "";
              let single = phone_codes.length === 1;
              if (single) {
                select = `<input disabled value="${phone_codes[0]}">`;
              } else {
                for (var i = 0; i < phone_codes.length; i++) {
                  select += `<option value="+${phone_codes[i]}">+${phone_codes[i]}</option>`;
                }
                select = `<select style='color:black!important;'><option value="">+00</option>${select}</select>`;
              }
              content += `<div id="phone" data-type="select-input" class="sb-input sb-input-select-input"><span class="${
                phone == "" ? "" : "sb-active sb-filled"
              }">${sb_("Phone")}</span><div>${select}</div><input value="${
                admin ? "" : phone
              }" pattern="[0-9]+" autocomplete="false" type="tel" ${
                settings["phone-required"] != "false" ? "required" : ""
              }></div>`;
            }
            content += `<div id="email" data-type="email" class="sb-input sb-input-btn"><span class="${
              email == "" ? "" : "sb-active sb-filled"
            }">${sb_(
              SBF.null(settings.placeholder) ? "Email" : settings["placeholder"]
            )}</span><input value="${email}" autocomplete="off" type="email" required><div class="sb-submit bi-chevron-right"></div></div>`;
            break;

          case "image":
            content = `<div class="sb-image"><img loading="lazy" src="${settings["url"]}"></div>`;
            break;

          case "video":
            content = `<iframe loading="lazy"${
              "height" in settings ? ` height="${settings["height"]}"` : ""
            } src="${
              settings["type"] == "youtube"
                ? "//www.youtube.com/embed/"
                : "//player.vimeo.com/video/"
            }${settings["id"]}" allowfullscreen></iframe>`;
            break;

          case "select":
            options = settings["options"].split(",");
            for (var i = 0; i < options.length; i++) {
              code += `<li data-value="${SBF.stringToSlug(options[i])}">${sb_(
                options[i]
              )}</li>`;
            }
            content = content.replace("[options]", code);
            break;

          case "chips":
          case "buttons":
            options = settings["options"].split(",");
            for (var i = 0; i < options.length; i++) {
              code += `<div class="sb-btn sb-submit">${sb_(options[i])}</div>`;
            }
            content = content.replace("[options]", code);
            break;

          case "list":
            options = settings["values"].split(",");
            let list = name == "list";
            let list_double =
              list && options.length && options[0].indexOf(":") > 0;
            if (list && !list_double) {
              content = content.replace(
                "sb-text-list",
                "sb-text-list sb-text-list-single"
              );
            }
            for (var i = 0; i < options.length; i++) {
              code += list_double
                ? `<div><div>${sb_(options[i].split(":")[0])}</div><div>${sb_(
                    options[i].split(":")[1]
                  )}</div></div>`
                : `<div>${$.trim(sb_(options[i]))}</div>`;
            }
            content = content.replace("[values]", code);
            break;

          case "list-image":
            options = settings["values"].split(",");
            for (var i = 0; i < options.length; i++) {
              let item = options[i].replace("://", "///").split(":");
              code += `<div><div class="sb-thumb" style="background-image:url('${item[0].replace(
                "///",
                "://"
              )}')"></div><div class="sb-list-title">${item[1]}</div><div>${
                item[2]
              }</div></div>`;
            }
            content = content.replace("[values]", code);
            break;

          case "table":
            options = settings["header"].split(",");
            code += "<tr>";
            for (var i = 0; i < options.length; i++) {
              code += `<th>${options[i]}</th>`;
            }
            code += "</tr>";
            content = content.replace("[header]", code);
            code = "";
            options = settings["values"].split(",");
            for (var i = 0; i < options.length; i++) {
              let tds = options[i].split(":");
              code += "<tr>";
              for (var i = 0; i < tds.length; i++) {
                code += `<td>${tds[i]}</td>`;
              }
              code += "</tr>";
            }
            content = content.replace("[values]", code);
            break;

          case "inputs":
            options = settings["values"].split(",");
            for (var i = 0; i < options.length; i++) {
              if (disabled && options[i] == "") continue;
              code += `<div id="${SBF.stringToSlug(
                options[i]
              )}" data-type="text" class="sb-input sb-input-text"><span>${sb_(
                options[i]
              )}</span><input autocomplete="false" type="text" required></div>`;
            }
            code +=
              '<div class="sb-btn sb-submit">' +
              sb_("button" in settings ? settings["button"] : "Send now") +
              "</div>";
            content = content.replace("[values]", code);
            break;

          case "card":
            code = `${
              "image" in settings
                ? `<div class="sb-card-img" style="background-image:url('${settings["image"]}')"></div>`
                : ""
            }<div class="sb-card-header">${settings["header"]}</div>${
              "extra" in settings
                ? `<div class="sb-card-extra">${settings["extra"]}</div>`
                : ""
            }${
              "description" in settings
                ? `<div class="sb-card-description">${settings["description"]}</div>`
                : ""
            }${
              "link" in settings
                ? `<a class="sb-card-btn" href="${settings["link"]}"${
                    "target" in settings ? ' target="_blank"' : ""
                  }>${sb_(settings["link-text"])}</a>`
                : ""
            }`;
            content = content.replace("[settings]", code);
            break;

          case "share":
            let channels =
              "channels" in settings
                ? settings["channels"].replace(/ /g, "").split(",")
                : ["fb", "tw", "li", "wa", "pi"];
            let link = "";
            for (var i = 0; i < channels.length; i++) {
              switch (channels[i]) {
                case "fb":
                  link = "www.facebook.com/sharer.php?u=";
                  break;
                case "tw":
                  link = "twitter.com/intent/tweet?url=";
                  break;
                case "li":
                  link = "www.linkedin.com/sharing/share-offsite/?url=";
                  break;
                case "wa":
                  link = "web.whatsapp.com/send?text=";
                  break;
                case "pi":
                  link = "www.pinterest.com/pin/create/button/?url=";
                  break;
              }
              code += `<div class="sb-${channels[i]} sb-icon-social-${channels[i]}" data-link="https://${link}${settings["link"]}"></div>`;
            }
            content = content.replace("[settings]", code);
            break;

          case "slider":
            let count = 0;
            for (var i = 1; i < 16; i++) {
              if ("header-" + i in settings) {
                code += `<div>${
                  "image-" + i in settings
                    ? `<div class="sb-card-img" style="background-image:url('${
                        settings["image-" + i]
                      }')"></div>`
                    : ""
                }<div class="sb-card-header">${settings["header-" + i]}</div>${
                  "extra-" + i in settings
                    ? `<div class="sb-card-extra">${
                        settings["extra-" + i]
                      }</div>`
                    : ""
                }${
                  "description-" + i in settings
                    ? `<div class="sb-card-description">${
                        settings["description-" + i]
                      }</div>`
                    : ""
                }${
                  "link-" + i in settings
                    ? `<a class="sb-card-btn" href="${settings["link-" + i]}"${
                        "target" in settings ? ' target="_blank"' : ""
                      }>${sb_(settings["link-text-" + i])}</a>`
                    : ""
                }</div>`;
                count++;
              } else {
                break;
              }
            }
            content = content
              .replace("[items]", code)
              .replace(/\[class\]/g, count == 1 ? " sb-hide" : "");
            break;

          case "slider-images":
            if ("images" in settings) {
              let images = settings["images"].split(",");
              for (var i = 0; i < images.length; i++) {
                code += `<div class="sb-card-img" data-value="${images[i]}" style="background-image:url('${images[i]}')"></div>`;
              }
              content = content.replace(
                /\[class\]/g,
                images.length == 1 ? " sb-hide" : ""
              );
            }
            content = content.replace("[items]", code);
            break;
          default:
            content = `[${name}]`; // Placeholder text for unmatched rich message names
            break;
        }
      }
      return (
        `<div id="${id}" data-type="${name}"${
          disabled ? 'disabled="true"' : ""
        }${
          "settings" in settings
            ? ` data-settings="${settings["settings"]}"`
            : ""
        } class="sb-rich-message sb-rich-${name} ${css}">` +
        ("title" in settings
          ? `<div class="sb-top">s${render.render(
              sb_(settings["title"])
            )}</div>`
          : "") +
        ("message" in settings
          ? `<div class="sb-text">${render.render(
              sb_(settings["message"])
            )}</div>`
          : "") +
        `<div class="sb-content">${content}</div><div data-success="${
          "success" in settings ? settings["success"].replace(/"/g, "") : ""
        }" class="sb-info"></div></div>`
      );
    },

    // Function of built-in rich messages
    submit: function (area, type, element) {
      if (!admin && !loading(element) && !this.is_busy) {
        let error = "";
        let shortcode = "";
        let parameters = {};
        let success = $(area).find("[data-success]").length
          ? $(area).find("[data-success]").attr("data-success")
          : "";
        let rich_message_id = $(area).closest(".sb-rich-message").attr("id");
        let message_id = $(area).closest("[data-id]").attr("data-id");
        let message = "";
        let payload = {
          "rich-messages": {},
        };
        let user_settings =
          activeUser() == false
            ? {
                profile_image: ["", ""],
                first_name: ["", ""],
                last_name: ["", ""],
                email: ["", ""],
                password: ["", ""],
                user_type: ["", ""],
              }
            : {
                profile_image: [activeUser().image, ""],
                first_name: [activeUser().get("first_name"), ""],
                last_name: [activeUser().get("last_name"), ""],
                email: [activeUser().get("email"), ""],
                password: ["", ""],
                user_type: ["", ""],
              };
        let settings = {};
        let input = $(element);
        let dialogflow_response = "";
        let dialogflow_parameters = false;
        let active_conversation = SBChat.conversation !== false;
        let settings_extra = {};
        let payload_settings = {};

        if (SBF.null(message_id)) {
          message_id = -1;
        } else {
          let item = SBChat.conversation.getMessage(message_id);
          message = item.message;
          payload = item.payload();
          if (!("rich-messages" in payload)) {
            payload["rich-messages"] = {};
          }
        }
        if (
          !$(element).hasClass("sb-btn") &&
          !$(element).hasClass("sb-select") &&
          !$(element).hasClass("sb-submit")
        ) {
          input = $(element).closest(".sb-btn,.sb-select");
        }
        $(area).find(".sb-info").html("").sbActive(false);

        switch (type) {
          case "email":
            let is_last_name = "last_name" in settings;
            settings = SBForm.getAll(area);
            if ("first_name" in settings) {
              user_settings["user_type"] = ["user", ""];
              if (!is_last_name) user_settings["last_name"] = ["", ""];
            }
            if ("phone" in settings) {
              settings_extra = {
                phone: [settings["phone"][0], "Phone"],
              };
            }
            $.extend(user_settings, settings);
            error =
              "Please fill in all required fields and make sure the email is valid.";
            if (success)
              success = sb_(success)
                .replace("{user_email}", user_settings["email"][0])
                .replace(
                  "{user_name_}",
                  user_settings["first_name"][0] +
                    (is_last_name ? " " + user_settings["last_name"][0] : "")
                );
            payload["rich-messages"][rich_message_id] = {
              type: type,
              result: settings,
            };
            payload["event"] = "update-user";
            parameters = {
              function: "update-user-and-message",
              settings: user_settings,
              settings_extra: settings_extra,
              payload: payload,
            };
            dialogflow_parameters = {
              settings: user_settings,
              settings_extra: settings_extra,
            };
            break;
          case "registration":
            settings_extra = SBForm.getAll(area.find(".sb-form-extra"));
            $.extend(user_settings, SBForm.getAll(area.find(".sb-form-main")));
            payload_settings = $.extend({}, user_settings);
            if (success) {
              success = sb_(success);
            }
            if (CHAT_SETTINGS["registration-details"]) {
              success += '[list values="';
              for (var key in user_settings) {
                let value = user_settings[key][0].replace(/:|,/g, "");
                if (value) {
                  if (key == "profile_image") {
                    value = value.substr(value.lastIndexOf("/") + 1);
                  }
                  if (key == "password" || key == "password-check") {
                    value = "********";
                    payload_settings[key] = "********";
                  }
                  success +=
                    user_settings[key][1] == ""
                      ? ""
                      : `${sb_(
                          user_settings[key][1].replace(/:|,/g, "")
                        )}:${value},`;
                }
              }
              for (var key in settings_extra) {
                if (settings_extra[key][0]) {
                  success += `${sb_(
                    settings_extra[key][1].replace(/:|,/g, "")
                  )}:${settings_extra[key][0].replace(/:|,/g, "")},`;
                }
              }
              success = success.slice(0, -1) + '"]';
            }
            user_settings["user_type"] = ["user", ""];
            payload["rich-messages"][rich_message_id] = {
              type: type,
              result: {
                user: payload_settings,
                extra: settings_extra,
              },
            };
            payload["event"] = "update-user";
            parameters = {
              function: activeUser()
                ? "update-user-and-message"
                : "add-user-and-login",
              settings: user_settings,
              settings_extra: settings_extra,
              payload: payload,
            };
            error = SBForm.getRegistrationErrorMessage(area);
            dialogflow_parameters = {
              settings: user_settings,
              settings_extra: settings_extra,
            };
            break;
          case "rating":
            let rating = $(element).attr("data-rating");
            let agent = SBChat.conversation.getLastUserMessage(false, true);
            success = `${sb_(
              success == "" ? sb_("Thank you for your feedback!") : success
            )}`;
            settings = {
              conversation_id: SBChat.conversation.id,
              agent_id: agent ? agent.get("user_id") : bot_id,
              user_id: activeUser().id,
              message: area.find("textarea").val(),
              rating: rating == "positive" ? 1 : -1,
            };
            payload["rich-messages"][rich_message_id] = {
              type: type,
              result: settings,
            };
            parameters = {
              function: "set-rating",
              payload: payload,
              settings: settings,
            };
            dialogflow_response = rating;
            break;
          case "chips":
          case "select":
          case "buttons":
            settings = SBF.escape($(element).html());
            success = success == "" ? success : sb_(success) + ` *${settings}*`;
            payload["rich-messages"][rich_message_id] = {
              type: type,
              result: settings,
            };
            parameters = {
              function: "update-message",
              payload: payload,
            };
            dialogflow_response = settings;
            if (type == "chips") {
              SBChat.sendMessage(
                activeUser().id,
                settings,
                [],
                false,
                {
                  id: rich_message_id,
                  event: "chips-click",
                  result: settings,
                },
                rich_message_id == "sb-human-takeover" && input.index() == 0
                  ? 2
                  : false
              );
              if (
                rich_message_id == "sb-human-takeover" &&
                $(element).index() == 0
              ) {
                SBApps.dialogflow.humanTakeover();
              }
              $(element).closest(".sb-content").remove();
            }
            break;
          case "inputs":
            settings = SBForm.getAll(area);
            error = "All fields are required.";
            if (success) {
              success = sb_(success) + ' [list values="';
              for (var key in settings) {
                success += `${sb_(
                  settings[key][1].replace(/:|,/g, "")
                )}:${settings[key][0].replace(/:|,/g, "")},`;
              }
              success = success.slice(0, -1) + '"]';
            }
            payload["rich-messages"][rich_message_id] = {
              type: type,
              result: settings,
            };
            parameters = {
              function: "update-message",
              payload: payload,
            };
            dialogflow_parameters = {
              settings: settings,
            };
            break;
        }

        shortcode = message.substr(message.indexOf("[" + type));
        shortcode = shortcode.substr(0, shortcode.indexOf("]") + 1);

        if (error && SBForm.errors(area)) {
          SBForm.showErrorMessage(area, error);
          input.sbLoading(false);
          if (
            SBChat.dashboard ||
            (active_conversation &&
              SBChat.conversation.getLastMessage().id == message_id)
          ) {
            SBChat.scrollBottom();
          }
          return false;
        }
        if (!success && type != "registration") {
          let shortcode_settings = this.shortcode(shortcode);
          let title =
            "title" in shortcode_settings[1]
              ? `title="${shortcode_settings[1]["title"]}"`
              : "";
          let message =
            "message" in shortcode_settings[1]
              ? `message="${shortcode_settings[1]["message"]}"`
              : "";
          let value = "";
          if (["inputs", "email"].includes(type)) {
            for (var key in settings) {
              value += settings[key][0] + ",";
            }
            value = `values="${value.slice(0, -1)}"`;
          } else {
            value = `options="${settings}"`;
          }
          success = `[${
            type == "email" ? "inputs" : type
          } ${title} ${message} ${value} disabled="true"]`;
        }
        if (message_id != -1) {
          $.extend(parameters, {
            message_id: message_id,
            message: message ? message.replace(shortcode, success) : success,
            payload: payload,
          });
        }
        SBF.ajax(parameters, (response) => {
          if (response != false && !SBF.errorValidation(response)) {
            switch (type) {
              case "email":
                for (var key in user_settings) {
                  activeUser().set(key, user_settings[key][0]);
                }
                for (var key in settings_extra) {
                  activeUser().setExtra(key, settings_extra[key][0]);
                }
                SBF.loginCookie(response[1]);
                if (rich_message_id == "sb-subscribe-form") {
                  SBF.ajax({
                    function: "subscribe-email",
                    email: activeUser().get("email"),
                  });
                }
                SBChat.automations.run_all();
                SBF.event("SBNewEmailAddress", {
                  id: rich_message_id,
                  name: activeUser().name,
                  email: activeUser().get("email"),
                });
                break;
              case "registration":
                SBF.loginCookie(response[1]);
                user_settings["id"] = [response[0].id];
                if (!activeUser()) {
                  activeUser(new SBUser(response[0]));
                  for (var key in settings_extra) {
                    activeUser().setExtra(key, settings_extra[key][0]);
                  }
                  SBPusher.start();
                  SBChat.initChat();
                  if (
                    (!CHAT_SETTINGS["init-dashboard"] ||
                      !main.find(".sb-departments-list").length) &&
                    success
                  )
                    SBChat.sendMessage(bot_id, success, [], false, false, 3);
                } else {
                  for (var key in user_settings) {
                    activeUser().set(key, user_settings[key][0]);
                  }
                  for (var key in settings_extra) {
                    activeUser().setExtra(key, settings_extra[key][0]);
                  }
                  SBChat.automations.run_all();
                }
                if (SBChat.dashboard) {
                  main.removeClass("sb-init-form-active");
                  $(area).remove();
                  if (!SBChat.isInitDashboard()) SBChat.hideDashboard();
                }

                delete this.cache["registration"];
                setTimeout(() => {
                  SBF.event("SBRegistrationForm", {
                    id: rich_message_id,
                    conversation_id: SBChat.conversation
                      ? SBChat.conversation.id
                      : false,
                    user: user_settings,
                    extra:
                      payload["rich-messages"][rich_message_id]["result"][
                        "extra"
                      ],
                  });
                }, 5000);
                break;
            }
            if (message_id == -1) {
              $(element).closest(".sb-rich-message").html(success);
            } else if (
              (!("type" in payload) || payload["type"] != "close-message") &&
              !dialogflow_human_takeover
            ) {
              SBChat.setConversationStatus(2);
            }
            if (
              !["login", "chips", "rating"].includes(type) &&
              (CHAT_SETTINGS["dialogflow-send-user-details"] ||
                !["email", "registration"].includes(type))
            ) {
              SBApps.dialogflow.message(
                `${rich_message_id}${
                  dialogflow_response ? "|" + dialogflow_response : ""
                }`,
                [],
                false,
                dialogflow_parameters
              );
            }
            if (
              CHAT_SETTINGS["slack-active"] &&
              (!dialogflow_human_takeover ||
                SBApps.dialogflow.humanTakeoverActive())
            ) {
              SBChat.slackMessage(
                activeUser().id,
                activeUser().name,
                activeUser().image,
                success
              );
            }
            if (SBPusher.active) SBChat.update();
            if (type != "registration" && type != "email")
              SBF.event("SBRichMessageSubmit", {
                result: response,
                data: payload["rich-messages"][rich_message_id],
                id: rich_message_id,
              });
          } else {
            SBForm.showErrorMessage(
              area,
              SBForm.getRegistrationErrorMessage(response, "response")
            );
            if (SBChat.dashboard) {
              SBChat.scrollBottom();
            }
            input.sbLoading(false);
          }
        });
      }
    },

    // Return the shortcode name and the shortcode settings
    shortcode: function (shortcode) {
      if (shortcode.indexOf(" ") < 0) {
        return [shortcode.slice(1, -1), {}];
      }
      let result = {};
      let shortcode_name = shortcode.substr(1, shortcode.indexOf(" ") - 1);
      shortcode = shortcode.slice(1, -1).substr(shortcode_name.length + 1);
      let settings = shortcode.split('" ');
      for (var i = 0; i < settings.length; i++) {
        if (settings[i].includes("=")) {
          let item = [
            settings[i].substr(0, settings[i].indexOf("=")),
            settings[i].substr(settings[i].indexOf("=") + 2),
          ];
          result[$.trim(item[0])] = item[1].replace(/"/g, "");
        }
      }
      return [shortcode_name, result];
    },

    // Init the rich message inputs
    initInputs: function (code) {
      code = $($.parseHTML("<div>" + code + "</div>"));
      code.find(".sb-input input").each(function () {
        if ($(this).val()) {
          $(this).siblings().addClass("sb-active sb-filled");
        }
      });
      return code.html();
    },

    timetable: function (code) {
      let table = $($.parseHTML(`<div>${code}</div>`));
      let offset = table.find("[data-offset]").attr("data-offset");
      offset = SBF.null(offset) ? 0 : parseInt(offset);
      let text = "";
      table.find("[data-time]").each(function () {
        let times = $(this).attr("data-time").split("|");
        for (var i = 0; i < times.length; i++) {
          if (times[i] == "closed") {
            text += "Closed";
            break;
          } else if (times[i]) {
            let hm = times[i].split(":");
            let time = SBF.convertUTCDateToLocalDate(
              `01/01/2000 ${hm[0]}:${hm[1]}`,
              offset
            );
            text +=
              time.toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
              }) +
              (i == 0 || i == 2
                ? " (to) "
                : i == 1 && times[i + 1]
                ? "\n"
                : "");
          }
        }
        text += "\n";
      });
      text += "Time zone " + Intl.DateTimeFormat().resolvedOptions().timeZone;
      return text;
    },

    // Slider
    sliderChange: function (id, direction = "left") {
      let slider = chat.find(`#${id}`);
      if (slider.length && !slider.hasClass("sb-moving")) {
        let items = slider.find(".sb-slider > div > div");
        let item = items.eq(0);
        let width = Math.ceil(item.closest(".sb-slider").width());
        let negative = direction == "right" ? -1 : 1;
        let margin =
          parseFloat(
            parseFloat(
              parseFloat(item.css("margin-left")) + width * negative
            ).toFixed(2)
          ) +
          -0.5 * negative;
        let check = width * (items.length - 1) * -1;
        if (margin < 1 && margin >= check) {
          item.css("margin-left", margin + "px");
          slider.addClass("sb-moving");
          setTimeout(() => {
            slider.removeClass("sb-moving");
          }, 1200);
        }
        slider
          .find(".bi-chevron-right")
          .sbActive(!(check > margin - 15 && check < margin + 15));
        slider.find(".bi-chevron-left").sbActive(margin < -10);
      }
    },
  };

  /*
   * ----------------------------------------------------------
   * FORM METHODS
   * ----------------------------------------------------------
   */

  var SBForm = {
    // Get all settings
    getAll: function (area) {
      let settings = {};
      $(area)
        .find(".sb-input[id]")
        .each((i, element) => {
          settings[$(element).attr("id")] = this.get(element);
        });
      return settings;
    },

    // Get a single setting
    get: function (input) {
      input = $(input);
      let type = input.data("type");
      let name = sb_(SBF.escape(input.find(" > span").html()));
      switch (type) {
        case "image":
          let url = input.find(".image").attr("data-value");
          return [SBF.null(url) ? "" : url, name];
        case "select":
          return [SBF.escape(input.find("select").val()), name];
        case "select-input":
          let select = input.find("select,input[disabled]");
          let prepend = select.val();
          return [
            SBF.escape(
              (select.is("select") || select.is("input")
                ? prepend
                : input.find("> div").html()) +
                input.find('input[type="tel"]').val()
            ),
            name,
          ];
        default:
          let target = input.find("input");
          return [
            SBF.escape(
              target.length
                ? target.val()
                : input.find("[data-value]").attr("data-value")
            ),
            name,
          ];
      }
    },

    // Set a single setting
    set: function (item, value) {
      item = $(item);
      if (item.length) {
        let type = item.data("type");
        switch (type) {
          case "image":
            if (value == "") {
              item.find(".image").removeAttr("data-value").removeAttr("style");
            } else {
              item
                .find(".image")
                .attr("data-value", value)
                .css("background-image", `url("${value}")`);
            }
            break;
          case "select":
            item.find("select").val(value);
            break;
          default:
            item.find("input,textarea").val(value);
            break;
        }
        return true;
      }
      return false;
    },

    // Clear all the input values
    clear: function (area) {
      $(area)
        .find(".sb-input,.sb-input-setting")
        .each((i, element) => {
          this.set(element, "");
          $(element).find("input, select, textarea").removeClass("sb-error");
        });
      this.set($(area).find("#user_type"), "lead");
    },

    // Check for errors on user input uLtUDvmLz@ZirfUT
    errors: function (area) {
      let errors = false;
      let items = $(area)
        .find("input, select, textarea")
        .removeClass("sb-error");
      items.each(function (i) {
        let value = $.trim($(this).val());
        let type = $(this).attr("type");
        let required = $(this).prop("required");
        if (
          (required && value == "") ||
          ((required || value) &&
            ((type == "password" &&
              (value.length < 8 ||
                (items.length > i + 1 &&
                  items.eq(i + 1).attr("type") == "password" &&
                  items.eq(i + 1).val() != value))) ||
              (type == "email" &&
                (value.indexOf("@") < 0 ||
                  /;|:|\/|\\|,|#|"|!|=|\+|\*|{|}|[|]|£|\$|€|~|'|>|<|\^|&/.test(
                    value
                  ))) ||
              (type == "tel" &&
                value &&
                ($(this).parent().find("select").val() == "" ||
                  isNaN(value) ||
                  value.includes("+") ||
                  value.length < 5))))
        ) {
          errors = true;
          $(this).addClass("sb-error");
        }
      });
      items = $(area).find("[data-required]").removeClass("sb-error");
      items.each(function () {
        if (SBF.null($(this).attr("data-value"))) {
          $(this).addClass("sb-error");
          errors = true;
        }
      });
      return errors;
    },

    // Display a error message
    showErrorMessage: function (area, message) {
      $(area).find(".sb-info").html(sb_(message)).sbActive(true);
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        $(area).find(".sb-info").sbActive(false);
      }, 2500);
    },

    // Display a success message
    showSuccessMessage: function (area, message) {
      $(area).find(".sb-info").remove();
      $(area)
        .addClass("sb-success")
        .find(".sb-content")
        .html(`<div class="sb-text">${message}</div>`);
    },

    // Return the registration error message
    getRegistrationErrorMessage(area_or_response, type = "validation") {
      if (type == "response")
        return SBF.errorValidation(area_or_response, "duplicate-email")
          ? "This email is already in use. Please use another email."
          : SBF.errorValidation(area_or_response, "duplicate-phone")
          ? "This phone number is already in use. Please use another number."
          : "Error. Please check your information and try again.";
      let name = $(area_or_response).find("#last_name").length
        ? "First name, last name"
        : "Name";
      let phone = $(area_or_response).find("#phone [required]").length
        ? ", phone number"
        : "";
      let email = $(area_or_response).find("#email").length ? ", email" : "";
      let password = $(area_or_response).find("#password").length
        ? ", and a password (8 character minimum)"
        : "";
      return `${name}${email}${phone}${password} ${
        email + phone + password == "" ? "is" : "are"
      } required.`;
    },
  };
  window.SBForm = SBForm;

  /*
   * ----------------------------------------------------------
   * # APPS
   * ----------------------------------------------------------
   */

  var SBApps = {
    // Get the login data
    login: function () {
      if (
        this.is("wp") &&
        typeof SB_WP_ACTIVE_USER != ND &&
        CHAT_SETTINGS["wp-users-system"] == "wp"
      ) {
        return [
          [SB_WP_ACTIVE_USER, typeof SB_WP_AVATAR != ND ? SB_WP_AVATAR : ""],
          "wp",
        ];
      }
      if (typeof SB_PERFEX_ACTIVE_USER != ND) {
        return [[SB_PERFEX_ACTIVE_USER, SB_PERFEX_CONTACT_ID], "perfex"];
      }
      if (typeof SB_WHMCS_ACTIVE_USER != ND) {
        return [SB_WHMCS_ACTIVE_USER, "whmcs"];
      }
      if (typeof SB_AECOMMERCE_ACTIVE_USER != ND) {
        return [SB_AECOMMERCE_ACTIVE_USER, "aecommerce"];
      }
      if (typeof SB_DEFAULT_USER != ND) {
        return [SB_DEFAULT_USER, "default"];
      }
      return false;
    },

    // Check if an app is installed and active
    is: function (name) {
      if (admin) return SBAdmin.apps.is(name);
      if (name == "wordpress" || name == "wp") return CHAT_SETTINGS["wp"];
      return name in CHAT_SETTINGS ? CHAT_SETTINGS[name] : false;
    },

    wordpress: {
      // Ajax
      ajax: function (action, data, onSuccess = false) {
        if (typeof SB_WP_AJAX_URL == ND) return;
        $.ajax({
          method: "POST",
          url: SB_WP_AJAX_URL,
          data: $.extend(
            {
              action: "sb_wp_ajax",
              type: action,
            },
            data
          ),
        }).done((response) => {
          if (onSuccess) {
            onSuccess(response);
          }
        });
      },
    },

    dialogflow: {
      token: storage("dialogflow-token"),
      typing_enabled: false,
      project_id: false,

      // Send a message to the bot and get the reply
      message: function (
        message = "",
        attachments = [],
        delay = false,
        parameters = false
      ) {
        if (this.active()) {
          let human_takeover_active = SBApps.dialogflow.humanTakeoverActive();
          if (!human_takeover_active || this.typing_enabled) {
            timeout = setTimeout(() => {
              SBChat.typing(-1, "start");
              setTimeout(() => {
                SBChat.typing(-1, "stop");
              }, 10000);
            }, 1000);
          }
          setTimeout(
            () => {
              SBF.ajax(
                {
                  function: "dialogflow-message",
                  conversation_id: SBChat.conversation
                    ? SBChat.conversation.id
                    : false,
                  message: message,
                  attachments: attachments,
                  parameters: parameters,
                  token: this.token,
                  dialogflow_language: storage("dialogflow-language")
                    ? storage("dialogflow-language")
                    : SB_LANG,
                  project_id: this.project_id,
                },
                (response) => {
                  if (response === false) return;
                  if ("human_takeover" in response) {
                    SBChat.offlineMessage();
                    SBChat.followUp();
                    return this.active(false);
                  }
                  if (
                    "language_detection" in response &&
                    !storage("dialogflow-language")
                  ) {
                    storage("dialogflow-language", [
                      response.language_detection,
                    ]);
                  }
                  if (!SBF.errorValidation(response)) {
                    let query_result =
                      "queryResult" in response["response"]
                        ? response["response"]["queryResult"]
                        : false;
                    let is_unknow =
                      query_result &&
                      (query_result["action"] == "input.unknown" ||
                        ("match" in query_result &&
                          query_result["match"]["matchType"] == "NO_MATCH"));
                    let messages =
                      "messages" in response &&
                      Array.isArray(response["messages"])
                        ? response["messages"]
                        : [];
                    SBChat.typing(-1, "stop");
                    clearTimeout(timeout);
                    if (this.token != response["token"]) {
                      this.token = response["token"];
                      storage("dialogflow-token", response["token"]);
                    }
                    if (is_unknow) {
                      if (human_takeover_active) this.typing_enabled = false;
                    } else this.typing_enabled = true;
                    if (query_result) {
                      // Actions
                      if ("action" in query_result) {
                        let action = query_result["action"];
                        if (action == "end") {
                          this.active(false);
                        }
                        SBF.event("SBBotAction", action);
                      }

                      // Payload
                      for (var i = 0; i < messages.length; i++) {
                        if ("payload" in messages[i]) {
                          let payloads = [
                            "human-takeover",
                            "redirect",
                            "transcript",
                            "department",
                            "agent",
                            "send-email",
                            "disable-bot",
                            "rich-message",
                          ];
                          let payload = messages[i]["payload"];
                          if (SBF.null(payload)) payload = [];
                          if (
                            payloads[0] in payload &&
                            payload[payloads[0]] === true
                          ) {
                            this.humanTakeover();
                          }
                          if (payloads[1] in payload) {
                            setTimeout(function () {
                              "new-window" in payload && payload["new-window"]
                                ? window.open(payload[payloads[1]])
                                : (document.location = payload[payloads[1]]);
                            }, 500);
                          }

                          if (payloads[4] in payload) {
                            SBChat.showArticles(payload[payloads[4]]);
                          }
                          if (payloads[5] in payload && payload[payloads[5]]) {
                            SBF.ajax(
                              {
                                function: "transcript",
                                conversation_id: SBChat.conversation.id,
                                type: "txt",
                              },
                              (response) => {
                                if (
                                  payload[payloads[5]] == "email" &&
                                  activeUser().get("email")
                                ) {
                                  SBChat.sendEmail(
                                    "message" in payload ? payload.message : "",
                                    [[response, response]],
                                    true
                                  );
                                } else window.open(response);
                              }
                            );
                          }
                          if (payloads[6] in payload) {
                            SBF.ajax({
                              function: "update-conversation-department",
                              conversation_id: SBChat.conversation.id,
                              department: payload[payloads[6]],
                              message:
                                SBChat.conversation.getLastUserMessage()
                                  .message,
                            });
                          }
                          if (payloads[7] in payload) {
                            SBF.ajax({
                              function: "update-conversation-agent",
                              conversation_id: SBChat.conversation.id,
                              agent_id: payload[payloads[7]],
                              message:
                                SBChat.conversation.getLastUserMessage()
                                  .message,
                            });
                          }
                          if (payloads[8] in payload) {
                            let email = payload[payloads[8]];
                            SBChat.sendEmail(
                              email.message,
                              email.attachments,
                              email.recipient == "active_user"
                            );
                          }
                          if (payloads[9] in payload) {
                            this.active(false);
                            SBF.cookie(
                              "sb-dialogflow-disabled",
                              true,
                              300,
                              "set",
                              true
                            );
                          }
                          if (payloads[10] in payload) {
                            SBChat.sendMessage(bot_id, payload[payloads[10]]);
                          }
                          SBF.event("SBBotPayload", payload);
                        }

                        // More
                        if (
                          !storage("dialogflow-language") &&
                          "languageCode" in query_result &&
                          (!SB_LANG || query_result.languageCode != SB_LANG[0])
                        ) {
                          storage("dialogflow-language", [
                            query_result.languageCode,
                          ]);
                        }
                      }

                      // Diagnostic info
                      if ("diagnosticInfo" in query_result) {
                        let info = query_result["diagnosticInfo"];

                        // End conversation
                        if (
                          "end_conversation" in info &&
                          info["end_conversation"]
                        ) {
                          this.active(false);
                        }
                      }
                    }

                    // Slack
                    // if (CHAT_SETTINGS['slack-active'] && messages && (!dialogflow_human_takeover || human_takeover_active)) {
                    // 	for (var i = 0; i < messages.length; i++) {
                    // 		SBChat.slackMessage(activeUser().id, CHAT_SETTINGS['bot-name'], CHAT_SETTINGS['bot-image'], messages[i]['message'], messages[i]['attachments']);
                    // 	}
                    // }

                    SBF.event("SBBotMessage", {
                      response: response,
                      message: message,
                    });
                  }
                }
              );
            },
            delay !== false
              ? delay
              : CHAT_SETTINGS["bot-delay"] == 0
              ? 2000
              : parseInt(CHAT_SETTINGS["bot-delay"])
          );
        }
      },

      // Check if Dialogflow is active or deactivate it
      active: function (active = true) {
        let name = "human-takeover-" + SBChat.conversation.id;
        if (active === false) {
          SBF.storageTime(name);
          return false;
        }
        if (active == "activate") {
          SBF.cookie("sb-dialogflow-disabled", 0, 0, "delete");
          SBF.storage(name, "");
        }
        return (
          CHAT_SETTINGS["dialogflow-active"] &&
          !admin &&
          !SBF.cookie("sb-dialogflow-disabled") &&
          (SBF.storageTime(name, 240) || !SBChat.agent_online) &&
          (!CHAT_SETTINGS["bot-office-hours"] || !CHAT_SETTINGS["office-hours"])
        );
      },

      // Trigger the welcome Intent
      welcome: function (open = false, sound = false) {
        SBF.ajax(
          {
            function: "dialogflow-message",
            message: "",
            conversation_id: SBChat.conversation.id,
            token: this.token,
            event: "Welcome",
            dialogflow_language: storage("dialogflow-language")
              ? storage("dialogflow-language")
              : SB_LANG,
          },
          () => {
            if (open) SBChat.start();
            if (sound) SBChat.audio.play();
          }
        );
      },

      // Start the human takeover process
      humanTakeover: function () {
        SBF.ajax(
          {
            function: "dialogflow-human-takeover",
            conversation_id: SBChat.conversation.id,
          },
          () => {
            SBChat.offlineMessage();
            SBChat.followUp();
            this.active(false);
            if (CHAT_SETTINGS["queue-human-takeover"]) {
              CHAT_SETTINGS["queue"] = true;
              SBChat.queue(SBChat.conversation.id);
            }
          }
        );
      },

      // Check if human takeover is active
      humanTakeoverActive: function () {
        return !SBF.storageTime(
          "human-takeover-" +
            (SBChat.conversation
              ? SBChat.conversation.id
              : storage("open-conversation")),
          240
        );
      },

      // Translate a string
      translate: function (strings, language_code, onSuccess) {
        SBF.ajax(
          {
            function: "google-translate",
            strings: strings,
            language_code: language_code,
            token: this.token,
          },
          (response) => {
            this.token = response[1];
            onSuccess(response[0]);
          }
        );
      },
    },
  };
  window.SBApps = SBApps;

  /*
   * ----------------------------------------------------------
   * # INIT
   * ----------------------------------------------------------
   */

  $(document).ready(function () {
    main = $(".sb-admin, .sb-admin-start");
    if (main.length) {
      admin = true;
      initialize();
      return;
    }
    let url_full;
    let url;
    let init = false;
    if (typeof SB_INIT_URL != ND) {
      if (SB_INIT_URL.indexOf(".js") < 0) {
        SB_INIT_URL += "/js/min/main.js";
      }
      url_full = SB_INIT_URL;
    } else {
      let scripts = document.getElementsByTagName("script");
      let checks = ["min/main.js"];

      for (var i = 0; i < scripts.length; i++) {
        let source = scripts[i].src;

        if (scripts[i].id == "routin") {
          url_full = source;
          init = init ? init : url_full.includes("init.");
          break;
        } else {
          for (var j = 0; j < checks.length; j++) {
            if (source.includes("/c/js/" + checks[j])) {
              url_full = source;
              init = init ? init : url_full.includes("init.");
              break;
            }
          }
        }
      }
    }
    let parameters = SBF.getURL(false, url_full);
    if ("url" in parameters) {
      url_full = parameters["url"];
    }
    if (typeof SB_DISABLED != ND && SB_DISABLED) {
      return;
    }
    if (init) {
      initialize();
      return;
    }
    if (
      typeof SB_TICKETS != ND ||
      ("mode" in parameters && parameters["mode"] == "tickets")
    ) {
      tickets = true;
      parameters["mode"] = "tickets";
    }
    if ("cloud" in parameters) {
      cloud_data = parameters["cloud"];
    }
    let min = url_full.lastIndexOf("main.js");
    url = url_full.substr(
      0,
      url_full.lastIndexOf("main.js") > 0
        ? url_full.lastIndexOf("main.js") - 4
        : min - 8
    );
    let url_chat =
      url +
      "/include/init.php" +
      ("lang" in parameters ? "?lang=" + parameters["lang"] : "") +
      ("mode" in parameters ? "&mode=" + parameters["mode"] : "") +
      (cloud_data ? "&cloud=" + cloud_data : "");
    SBF.cors("GET", url_chat.replace(".php&", ".php?"), (response) => {
      let target = "body";
      if (tickets && $("#sb-tickets").length) {
        target = "#sb-tickets";
      }
      $(target).append(response);
      SBF.loadResource(url + "/css/" + (tickets ? "tickets" : "main") + ".css");
      if (tickets) {
        $.getScript(
          url +
            "/apps/tickets/tickets" +
            (min ? ".min" : "") +
            ".js?v=" +
            version,
          () => {
            initialize();
          }
        );
      } else initialize();
    });
  });

  function initialize() {
    main = $(".sb-admin, .sb-chat, .sb-tickets");

    // Initalize the chat and the user
    if (main.length && typeof SB_AJAX_URL != ND) {
      chat = main.find(".sb-list");
      chat_editor = main.find(".sb-editor");
      
      chat_textarea = chat_editor.find("textarea");
      chat_scroll_area = main.find(
        admin || tickets ? ".sb-list" : "> div > .sb-scroll-area"
      );
      label_date = main.find(".sb-label-date-top");
      chat_header = chat_scroll_area.find(".sb-header");
      chat_emoji = chat_editor.find(".sb-emoji");
      chat_status = tickets ? main.find(".sb-profile-agent .sb-status") : null;
      SBChat.enabledAutoExpand();
      SBChat.audio = main.find("#sb-audio").get(0);
      SBChat.audio_out = main.find("#sb-audio-out").get(0);

      // Check if cookies works
      SBF.cookie("sb-check", "ok", 1, "set");
      if (SBF.cookie("sb-check") != "ok") {
        cookies_supported = false;
        console.warn("Routin.bot: cookies not available.");
      } else {
        SBF.cookie("sb-check", false, false, false);
      }
      if (!admin) {
        SBF.ajax(
          {
            function: "get-front-settings",
            current_url: window.location.href,
          },
          (response) => {
            CHAT_SETTINGS = response;
            if (typeof SB_LOCAL_SETTINGS != ND) {
              $.extend(CHAT_SETTINGS, SB_LOCAL_SETTINGS);
            }
            bot_id = CHAT_SETTINGS["bot-id"];
            dialogflow_human_takeover =
              CHAT_SETTINGS["dialogflow-human-takeover"];
            agents_online = CHAT_SETTINGS["agents-online"];
            SBPusher.active = CHAT_SETTINGS["pusher"];
            if (typeof SB_REGISTRATION_REQUIRED != ND) {
              CHAT_SETTINGS["registration-required"] = SB_REGISTRATION_REQUIRED;
              CHAT_SETTINGS["tickets-registration-required"] =
                SB_REGISTRATION_REQUIRED;
            }
            if (typeof SB_ARTICLES_PAGE != ND && SB_ARTICLES_PAGE) {
              SBChat.initArticlesPage();
            }
            if (
              (!tickets || !CHAT_SETTINGS["tickets-manual-init"]) &&
              ((tickets && !CHAT_SETTINGS["tickets-manual-init"]) ||
                (!CHAT_SETTINGS["chat-manual-init"] &&
                  (!CHAT_SETTINGS["disable-offline"] || agents_online) &&
                  (!CHAT_SETTINGS["disable-office-hours"] ||
                    CHAT_SETTINGS["office-hours"]) &&
                  (!CHAT_SETTINGS["chat-login-init"] || SBApps.login())))
            ) {
              SBChat.initChat();
            }
            if (CHAT_SETTINGS["cron"]) {
              setTimeout(function () {
                SBF.ajax({
                  function: "cron-jobs",
                });
              }, 10000);
            }
            if (CHAT_SETTINGS["cron-email-piping"]) {
              setTimeout(function () {
                SBF.ajax({
                  function: "email-piping",
                });
              }, 8000);
            }
            if (CHAT_SETTINGS["push-notifications-users"]) {
              SBPusher.initServiceWorker();
            }
            if (tickets) {
              if (CHAT_SETTINGS["tickets-default-department"])
                SBChat.default_department =
                  CHAT_SETTINGS["tickets-default-department"];
              if (CHAT_SETTINGS["dialogflow-disable-tickets"])
                CHAT_SETTINGS["dialogflow-active"] = false;
            }

            SBF.event("SBReady");
          }
        );
        if (SBChat.audio) SBChat.audio.volume = 0.6;
        if (SBChat.audio_out) SBChat.audio_out.volume = 0.6;
      } else {
        SBF.event("SBReady");
      }
      $(chat_editor).on("keydown", "textarea", function (e) {
        if (
          e.which == 13 &&
          (!tickets || CHAT_SETTINGS["tickets-enter-button"]) &&
          (!admin || (!e.ctrlKey && !e.shiftKey))
        ) {
          SBChat.submit();
          e.preventDefault;
          return false;
        }
        if (admin && e.which == 13 && e.ctrlKey) {
          SBChat.insertText("\n");
        }
      });
      $(main).on("keydown", ".sb-dashboard-articles input", function (e) {
        if (e.which == 13) $(this).next().click();
      });
      if (typeof SB_DEFAULT_DEPARTMENT !== ND) {
        SBChat.default_department = SB_DEFAULT_DEPARTMENT;
      }
      if (typeof SB_DEFAULT_AGENT !== ND) {
        SBChat.default_agent = SB_DEFAULT_AGENT;
      }
      if (typeof SB_DIALOGFLOW_TAGS !== ND) {
        SBChat.default_tags = SB_DEFAULT_TAGS;
      }
      if (typeof SB_DIALOGFLOW_AGENT !== ND) {
        SBApps.dialogflow.project_id = SB_DIALOGFLOW_AGENT;
      }
    } else {
      SBF.event("SBReady");
    }
    // Disable real-time if browser tab not active
    document.addEventListener(
      "visibilitychange",
      function () {
        SBF.visibilityChange(document.visibilityState);
      },
      false
    );

    $(main).on("click", function () {
      if (!SBChat.tab_active) {
        SBF.visibilityChange();
      }
    });

    // Set the global container for both admin and front
    global = main;
    if (admin) {
      main = main.find(".sb-conversation");
    }

    // Scroll detection
    $(chat_scroll_area).on("scroll", function () {
      let scroll = chat_scroll_area.scrollTop();
      let count = label_date_items.length;
      SBChat.scrollHeader();
      if (label_date_show) {
        label_date.sbActive(true);
        clearTimeout(timeout_label_date[0]);
        timeout_label_date[0] = setTimeout(() => {
          label_date.sbActive(false);
        }, 1500);
      }
      if (count) {
        if (SBChat.isBottom()) {
          label_date.html($(label_date_items[count - 1]).html());
        } else {
          for (var i = 0; i < count; i++) {
            let top = label_date_items[i].getBoundingClientRect().top;
            if (top > 100 && top < 150) {
              let label = $(
                label_date_items[
                  label_date_history[0] > scroll && i > 0 ? i - 1 : i
                ]
              ).html();
              if (label != label_date_history[1]) {
                label_date.html(label);
                label_date_history[1] = label;
              }
              break;
            }
          }
        }
      }
      label_date_history[0] = scroll;
    });

    // Show the message menu
    $(chat).on("click", ".sb-menu-btn", function () {
      let menu = $(this).parent().find(".sb-menu");
      let active = $(menu).sbActive();
      SBF.deactivateAll();
      if (!active) {
        $(menu).sbActive(true);
        SBF.deselectAll();
        if (admin) SBAdmin.open_popup = menu;
      }
    });

    // Mobile
    if (mobile) {
      $(chat_editor).on("click", ".sb-textarea", function () {
        main.addClass("sb-header-hidden");
        $(this).find("textarea").focus();
        if (SBChat.isBottom()) {
          SBChat.scrollBottom();
          setTimeout(() => {
            SBChat.scrollBottom();
          }, 200);
        }
      });

      $(chat_editor).on("focusout", ".sb-textarea", function () {
        setTimeout(() => {
          main.removeClass("sb-header-hidden");
        }, 300);
      });

      $(chat_editor).on("click", ".sb-submit", function () {
        chat_textarea.blur();
      });

      window.addEventListener("popstate", function () {
        if (SBChat.chat_open) SBChat.open(false);
      });
    }

    // Hide the message menu
    $(chat).on("click", ".sb-menu li", function () {
      $(this).parent().sbActive(false);
    });
    $(main).on("click", "#load-more", function () {
      let loadmore = new SBMessage();
      load_more += 30;
      console.log("set", CHAT_SETTINGS);
      loadmore.setLoad(load_more);
      $(this).text("");
      $(this).sbLoading(true);
      $(this).attr("id", "");
      activeUser().getFullConversation(
        SBChat.conversation.id,
        (response) => {
          $(this).sbLoading(false);
          $(this).html(
            '<i style="font-size:1.2rem" class="bi-arrow-up-circle"></i>'
          );
          $(this).attr("id", "load-more");
          SBChat.loadUpdate(response.messages);
        },
        loadmore.getLoad()
      );
      if (total_more <= loadmore.getLoad()) {
        $(".load-more").hide();
        // console.log("load new", total_more, loadmore.getLoad());
      }
    });
    // Send a message
    $(chat_editor).on("click", ".sb-submit", function () {
      SBChat.submit();
    });

    
    // Open the chat
    $("body").on(
      "click",
      ".sb-chat-btn,.sb-responsive-close-btn,#sb-open-chat,.sb-open-chat",
      function () {
        SBChat.open(!SBChat.chat_open);
      }
    );

    // Show the dashboard
    $(main).on("click", ".sb-dashboard-btn", function () {
      SBChat.showDashboard();
      if (chat_scroll_area.find(" > .sb-panel-articles > .sb-article").length)
        SBChat.showArticles();
      storage("open-conversation", 0);
      force_action = false;
    });
    // Send whatsapp template
    $("#template-form").on("submit", function (e) {
      e.preventDefault();
      const template = new Metatemplate().payload("#template-form");

      const payload = {
        type: template.type,
        to: activeUser().getExtra("phone").value,
        template_name: template.template_name,
        language: template.language,
        variables: template.variables,
        image_url: template.image_url,
      };
      console.log("Payload:", payload);

      const submit = $("#send-meta-template");

      submit.sbLoading(true);
      SBF.ajax(
        {
          function: "whatsapp-send-meta-template",
          payload: payload,
        },
        (response) => {
          submit.sbLoading(false);
          if (response?.error && response.error.message) {
            SBForm.showErrorMessage(
              $(".sb-admin").find(".sb-send-template-box"),
              response.error.message
            );
          } else if (response) {
            SBChat.sendMessage(
              -1,
              `*Plantilla WhatsApp*, {agent_name} \n\n ${template.BodyTemplate}`,
              false
            );
            SBChat.showResponse(`${template.BodyTemplate}`);
            $(admin ? global : main)
              .find(
                ".sb-lightbox-media, .sb-lightbox-overlay, .sb-send-template-box"
              )
              .sbActive(false);
          }
          console.log("temp", response);
        }
      );
    });

    //rate and review
    SBF.get_select_setting();

    // Open a conversation from the dashboard
    $(main).on("click", ".sb-user-conversations li", function () {
      SBChat.openConversation($(this).attr("data-conversation-id"));
    });

    // Start a new conversation from the dashboard
    $(main).on(
      "click",
      ".sb-btn-new-conversation,.sb-departments-list > div,.sb-agents-list > div",
      function () {
        let id = $(this).data("id");
        if (!SBF.null(id)) {
          if ($(this).parent().hasClass("sb-departments-list")) {
            SBChat.default_department = parseInt(id);
          } else {
            SBChat.default_agent = parseInt(id);
          }
        }
        force_action = "new-conversation";
        SBChat.clear();
        SBChat.hideDashboard();
      }
    );

    // Displays all conversations in the dashboard
    $(main).on("click", ".sb-btn-all-conversations", function () {
      main
        .find(".sb-dashboard-conversations")
        .removeClass("sb-conversations-hidden");
    });

    // Events uploader
    $(chat_editor).on("click", "#upload-files", function () {
      if (!SBChat.is_busy) {
        chat_editor.find(".sb-upload-files").val("").click();
        chat_editor.find(".bi-arrow-up-circle-fill").removeClass("sb-hide");

        console.log("to upload file");
      }
    });

    $(chat_editor).on("click", ".sb-attachments > div > i", function (e) {
      $(this).parent().remove();
      if (
        chat_textarea.val() == "" &&
        chat_editor.find(".sb-attachments > div").length == 0
      ) {
        SBChat.activateBar(false);
      }
      e.preventDefault();
      return false;
    });

    $(chat_editor).on("change", ".sb-upload-files", function (data) {
      SBChat.busy(true);
      $(this).sbUploadFiles(function (response) {
        SBChat.uploadResponse(response);
      });

      SBF.event("SBAttachments");
    });

    $(chat_editor).on("dragover", function (e) {
      $(this).addClass("sb-drag");
      clearTimeout(timeout);
      e.preventDefault();
      e.stopPropagation();
      // Show the send icon after a timeout
      showSendIconAfterTimeout();
    });

    $(chat_editor).on("dragleave", function (e) {
      timeout = setTimeout(() => {
        $(this).removeClass("sb-drag");
        // Show the send icon after a timeout
        showSendIconAfterTimeout();
      }, 200);
      e.preventDefault();
      e.stopPropagation();
    });

    $(chat_editor).on("drop", function (e) {
      let files = e.originalEvent.dataTransfer.files;
      e.preventDefault();
      e.stopPropagation();
      if (files.length > 0) {
        for (var i = 0; i < files.length; ++i) {
          let form = new FormData();
          form.append("file", files[i]);
          SBF.upload(form, function (response) {
            SBChat.uploadResponse(response);
          });
        }
      }
      $(this).removeClass("sb-drag");
      return false;
    });

    // Function to show the send icon after a timeout
    function showSendIconAfterTimeout() {
      setTimeout(() => {
        $(".bi-arrow-up-circle-fill").removeClass("sb-hide");
      }, 500); // Adjust the timeout duration as needed
    }

    // Articles
    $(main).on("click", ".sb-btn-all-articles:not([onclick])", function () {
      SBChat.showArticles();
    });

    $(main).on("click", ".sb-articles > div:not(.sb-title)", function () {
      SBChat.showArticles($(this).attr("data-id"));
    });

    $(main).on(
      "click",
      ".sb-dashboard-articles .sb-input-btn .sb-submit-articles",
      function () {
        SBChat.searchArticles(
          $(this).parent().find("input").val(),
          this,
          $(this).parent().next()
        );
      }
    );

    $(global).on("click", ".sb-article [data-rating]", function () {
      SBChat.articleRatingOnClick(this);
    });

    $(chat).on("click", ".sb-rich-button a", function (e) {
      let link = $(this).attr("href");
      if (link.indexOf("#") === 0) {
        if (link.indexOf("#article-") === 0) {
          SBChat.showArticles(link.replace("#article-", ""));
          e.preventDefault();
          return false;
        }
      }
    });

    // Lightbox
    $(global).on("click", ".sb-lightbox-media > i", function () {
      global.find(".sb-lightbox-media").sbActive(false);
      if (admin) SBAdmin.open_popup = false;
      return false;
    });

    $(main).on("click", ".sb-image", function () {
      SBF.lightbox($(this).html());
    });

    $(main).on("click", ".sb-slider-images .sb-card-img", function () {
      SBF.lightbox(
        `<img loading="lazy" src="${$(this).attr("data-value")}" />`
      );
    });

    $(main).on("click", ".sb-image", function () {
      SBF.lightbox($(this).html());
    });

    $(main).on("click", ".sb-slider-images .sb-card-img", function () {
      SBF.lightbox(
        `<img loading="lazy" src="${$(this).attr("data-value")}" />`
      );
    });

    // Event: on conversation loaded
    $(document).on("SBConversationLoaded", function () {
      if (storage("queue")) {
        SBChat.queue(storage("queue"));
      }
    });

    // Events emoji
    $(chat_editor).on("click", ".bi-emoji-grin", function () {
      SBChat.showEmoji(this);
    });

    $(chat_emoji).on("click", ".sb-emoji-list li", function (e) {
      SBChat.insertEmoji($(this).html());
      if (mobile) clearTimeout(timeout);
    });

    $(chat_emoji)
      .find(".sb-emoji-list")
      .on("touchend", function (e) {
        timeout = setTimeout(() => {
          SBChat.mouseWheelEmoji(e);
        }, 50);
      });

    $(chat_emoji)
      .find(".sb-emoji-list")
      .on("mousewheel DOMMouseScroll", function (e) {
        SBChat.mouseWheelEmoji(e);
      });

    // $(chat_emoji)
    //   .find(".sb-emoji-list")
    //   .on("touchstart", function (e) {
    //     SBChat.emoji_options["touch"] = e.originalEvent.touches[0].clientY;
    //   });

    $(chat_emoji)
      .find(".sb-emoji-list")
      .on(
        "touchstart",
        function (e) {
          SBChat.emoji_options["touch"] = e.originalEvent.touches[0].clientY;
        },
        { passive: true }
      );

    $(chat_emoji).on("click", ".sb-emoji-bar > div", function () {
      SBChat.clickEmojiBar(this);
    });

    $(chat_emoji).on("click", ".sb-select li", function () {
      SBChat.categoryEmoji($(this).data("value"));
    });

    $(chat_emoji)
      .find(".sb-search-btn input")
      .on("change keyup paste", function () {
        SBChat.searchEmoji($(this).val());
      });

    // Textarea
    $(chat_textarea).on("keyup", function () {
      SBChat.textareaChange(this);
    });

    // Privacy message
    $(main).on("click", ".sb-privacy .sb-approve", function () {
      storage("privacy-approved", true);
      $(this).closest(".sb-privacy").remove();
      main.removeClass("sb-init-form-active");
      chat_header.find(" > div").css({
        opacity: 1,
        top: 0,
      });
      SBChat.initChat();
      if (tickets) {
        SBTickets.showPanel("new-ticket");
      } else if (!SBChat.isInitDashboard()) {
        SBChat.hideDashboard();
      }
    });

    $(main).on("click", ".sb-privacy .sb-decline", function () {
      let privacy = $(this).closest(".sb-privacy");
      $(privacy).find(".sb-text").html($(privacy).attr("data-decline"));
      $(privacy).find(".sb-decline").remove();
      SBChat.scrollBottom(true);
    });

    // Popup message
    $(main).on("click", ".sb-popup-message .bi-x-lg", function () {
      SBChat.popup(true);
    });

    // Rich messages and inputs
    $(main).on(
      "click",
      ".sb-rich-message .sb-submit,.sb-rich-message .sb-select ul li",
      function () {
        let message = $(this).closest(".sb-rich-message");
        if (!message[0].hasAttribute("disabled")) {
          SBRichMessages.submit(message, message.attr("data-type"), this);
        }
      }
    );

    $(main).on("click", ".sb-rich-message .sb-input > span", function () {
      $(this).sbActive(true);
      $(this).siblings().focus();
    });

    $(main).on(
      "focus focusout click",
      ".sb-rich-message .sb-input input,.sb-rich-message .sb-input select",
      function (e) {
        switch (e.type) {
          case "focusin":
          case "focus":
            $(this).siblings().sbActive(true);
            break;
          case "focusout":
            if ($(this).val() == "") {
              $(this).siblings().sbActive(false);
            } else {
              $(this).siblings().addClass("sb-filled sb-active");
            }
            break;
          case "click":
            $(this).siblings().removeClass("sb-filled");
            break;
        }
      }
    );

    $(main).on("click", ".sb-slider-arrow", function () {
      SBRichMessages.sliderChange(
        $(this).closest("[id]").attr("id"),
        $(this).hasClass("bi-chevron-right") ? "right" : "left"
      );
    });

    $(main).on(
      "change",
      '.sb-rich-message [data-type="select"] select',
      function () {
        $(this).siblings().sbActive(true);
      }
    );

    $(main).on("click", '[data-type="select-input"] > div', function () {
      $(this).prev().sbActive(true);
      $(this).next().addClass("sb-focus");
    });

    $(main).on(
      "focusout",
      '[data-type="select-input"] input,[data-type="select-input"] select',
      function () {
        let cnt = $(this).closest(".sb-input");
        if (
          cnt.find('input[type="tel"]').val() + cnt.find("select").val() ==
          ""
        ) {
          cnt.find("span").sbActive(false);
        }
        cnt.find(".sb-focus").removeClass("sb-focus");
      }
    );

    // Registration and Login
    $(main).on("click", ".sb-rich-registration .sb-login-area", function () {
      let init = main.hasClass("sb-init-form-active");
      $(this)
        .closest(".sb-rich-registration")
        .replaceWith(
          SBRichMessages.generate({}, "login", init ? "sb-init-form" : "")
        );
      SBChat.scrollBottom(init);
    });

    $(main).on("click", ".sb-rich-login .sb-registration-area", function () {
      if (CHAT_SETTINGS["registration-link"]) {
        document.location = CHAT_SETTINGS["registration-link"];
      } else {
        let init = main.hasClass("sb-init-form-active");
        $(this)
          .closest(".sb-rich-login")
          .replaceWith(
            SBRichMessages.generate(
              {},
              "registration",
              init ? "sb-init-form" : ""
            )
          );
        SBChat.scrollBottom(init);
      }
    });

    $(main).on("click", ".sb-rich-login .sb-submit-login", function () {
      SBF.loginForm(this, false, (response) => {
        let area = $(this).closest(".sb-rich-login");
        activeUser(new SBUser(response[0]));
        if (area.hasClass("sb-init-form")) {
          main.removeClass("sb-init-form-active");
          area.remove();
          force_action = "open-conversation";
          SBChat.initChat();
          SBPusher.start();
          if (!SBChat.isInitDashboard()) {
            SBChat.hideDashboard();
          }
        } else {
          area = area.closest("[data-id]");
          let message = SBChat.conversation.getMessage(area.attr("data-id"));
          let text = `${sb_("Logged in as")} *${activeUser().name}*`;
          message.set("message", text);
          SBChat.updateMessage(message.id, text);
          area.replaceWith(message.getCode());
          SBPusher.started = false;
          SBPusher.start();
        }
      });
    });

    // Social share buttons
    $(chat).on("click", ".sb-social-buttons div", function () {
      SBF.openWindow($(this).attr("data-link"));
    });

    // Archive chat
    $(main).on("click", ".sb-close-chat", function () {
      SBChat.closeChat();
    });

    // $(chat).on("click", "#sb-waiting-list .sb-submit", function () {
    //   if ($(this).index() == 0) {
    //     setTimeout(() => {
    //       SBApps.woocommerce.waitingList("submit");
    //     }, 1000);
    //   }
    // });

    // $(document).on("SBNewEmailAddress", function (e, response) {
    //   if (response["id"] == "sb-waiting-list-email") {
    //     SBApps.woocommerce.waitingList("submit");
    //   }
    // });

    /*
     * ----------------------------------------------------------
     * COMPONENTS
     * ----------------------------------------------------------
     */

    // Search
    $(global).on("click", ".sb-search-btn > i", function () {
      let parent = $(this).parent();
      let active = $(parent).sbActive();
      if (active) {
        setTimeout(() => {
          $(parent).find("input").val("");
        }, 50);
        setTimeout(() => {
          $(parent).find("input").trigger("change");
        }, 550);
      }
      $(parent).sbActive(!active);
      $(parent).find("input").focus();
      global.find(".sb-select ul").sbActive(false);
    });

    // Select
    $(global).on("click", ".sb-select", function () {
      let ul = $(this).find("ul");
      let active = ul.hasClass("sb-active");
      $(global).find(".sb-select ul").sbActive(false);
      ul.setClass("sb-active", !active);
      if (admin) SBAdmin.open_popup = active ? false : this;
    });

    /**inbox loader**/
    $(global).on("click", ".sb-select li", function () {
      let select = $(this).closest(".sb-select");
      let value = $(this).data("value");
      var item = $(select).find(`[data-value="${value}"]`);
      $(select).find("li").sbActive(false);
      $(select).find("p").attr("data-value", value).html($(item).html());
      $(item).sbActive(true);
    });

    // Image uploader
    $(global).on("click", ".sb-input-image .image", function () {
      upload_target = $(this).parent();
      chat_editor.find(".sb-upload-files").click();
    });

    $(global).on("click", ".sb-input-image .image > .bi-x-lg", function (e) {
      $(this).parent().removeAttr("data-value").css("background-image", "");
      e.preventDefault();
      return false;
    });

    let alertOnConfirmation = false;

    function dialog(
      text,
      type,
      onConfirm = false,
      id = "",
      title = "",
      scroll = false
    ) {
      let box = global.find(".sb-dialog-box").attr("data-type", type);
      let p = box.find("p");
      box
        .attr("id", id)
        .setClass("sb-scroll-area", scroll)
        .css("height", scroll ? parseInt($(window).height()) - 200 + "px" : "");
      box.find(".sb-title").html(title);
      p.html((type == "alert" ? sb_("Are you sure?") + " " : "") + sb_(text));
      box.sbActive(true).css({
        "margin-top": box.outerHeight() / -2 + "px",
        "margin-left": box.outerWidth() / -2 + "px",
      });
      alertOnConfirmation = onConfirm;
      setTimeout(() => {
        SBAdmin.open_popup = box;
      }, 500);
    }

    // button API hide and show template

    async function getFirstActiveWAConversationItem() {
      await new Promise((resolve) => setTimeout(resolve, 1000));
      const conversationItem = document.querySelector(
        "li.sb-active[data-conversation-source='wa']"
      );
      return conversationItem;
    }
    window.onload = async () => {
      const conversationItem = await getFirstActiveWAConversationItem();
      if (conversationItem) {
        setTimeout(() => toggleMenuBarAndFloatingText(false), 20);
      } else {
        toggleMenuBarAndFloatingText(true);
      }
    };
    async function toggleMenuBarAndFloatingText(visible) {
      await new Promise((resolve) => setTimeout(resolve, 0));
      const menuBar = document.querySelector(".sb-show-menu-bar,.sb-bar");
      if (menuBar) {
        menuBar.style.visibility = visible ? "visible" : "hidden";
        menuBar.style.opacity = visible ? "1" : "0";
      }
      const floatingText = document.getElementById("floatingText");
      if (floatingText) {
        floatingText.style.display = visible ? "none" : "block";
        floatingText.style.opacity = visible ? "0" : "1";
      }
    }
    document.addEventListener("click", handleConversationClick);
    async function handleConversationClick(event) {
      if (event.target.closest("div.sb-header")) return;
      toggleElementsVisibility();
      const conversationItem = event.target.closest(
        "li.sb-active[data-conversation-source='wa'][data-conversation-status][data-user-id][data-conversation-id][data-time]"
      );
      if (!conversationItem) return;
      const source = conversationItem.dataset.conversationSource;
      const status = conversationItem.dataset.conversationStatus;
      if (source === "wa" && !isNaN(status)) {
        await toggleMenuBarAndFloatingText(false);
      }
    }
    function toggleElementsVisibility() {
      const floatingText = document.getElementById("floatingText");
      const container = document.querySelector(".sb-show-menu-bar");
      if (floatingText) {
        floatingText.style.opacity = "0";
        floatingText.style.display = "none";
      }
      if (container) {
        container.style.visibility = "visible";
        container.style.opacity = "1";
      }
    }
    const floatingText = document.getElementById("floatingText");
    if (floatingText) {
      floatingText.addEventListener("click", toggleElementsVisibility);
    }

    function addHideClass() {
      const whatsAppButton = document.querySelector(".api-whatsapp-button");
      if (whatsAppButton) {
        whatsAppButton.classList.add("sb-hide");
      }
    }

    function removeHideClass() {
      const whatsAppButton = document.querySelector(".api-whatsapp-button");
      if (whatsAppButton) {
        whatsAppButton.classList.remove("sb-hide");
      }
    }

    function toggleWhatsAppButton(visible) {
      if (visible) {
        removeHideClass();
      } else {
        addHideClass();
      }
    }

    function checkActiveWAConversation() {
      const conversationItem = document.querySelector(
        "li.sb-active[data-conversation-source='wa']"
      );
      toggleWhatsAppButton(conversationItem !== null);
    }
    document
      .querySelector(".menu-plus.bi-plus-lg")
      .addEventListener("click", function () {
        const conversationItem = document.querySelector(
          "li.sb-active[data-conversation-source='wa']"
        );
        if (conversationItem) {
          toggleWhatsAppButton(true);
        } else {
          toggleWhatsAppButton(false);
        }
      });

    document
      .getElementById("floatingText")
      .addEventListener("click", function () {
        toggleWhatsAppButton(false);
      });

    document.addEventListener("DOMContentLoaded", function () {
      checkActiveWAConversation();
    });


    // CONTEXTUAL BLOCKED
    $(document).on("contextmenu",function(e){
        return false;
    });

    // LISTENER
    $(global).on(
      "click",
      '.sb-message.sb-location, .sb-message p a[href^="https://maps.google.com/"]',
      function (e) {
        e.preventDefault();
        let location = $(this).closest(".sb-location").attr("data-location");
        let url = $(this).attr("href");

        if (!location) {
          const matches = url.match(/\/\?q=([\d\.\-]+),([\d\.\-]+)/);
          if (matches && matches.length >= 3) {
            location = matches[1] + "," + matches[2];
          }
        }
        if (location) {
          const iframe =
            '<iframe src="https://maps.google.com/maps?q=' +
            location +
            '&output=embed"></iframe>';
          $(this).closest(".sb-message").html(iframe);
        } else {
          window.open(url, "_blank");
        }
      }
    );
  }
})(jQuery);

// newConversation source inyector
var selectedSource = "tk";
function updateSource(value) {
  selectedSource = value;
  console.log("Selected Source:", selectedSource);
}
let isSpeechSynthesisActive = false;
let utterance = null; // Declare utterance globally to access it from the event listener

// Function to read text
function readText(text) {
  if (!isSpeechSynthesisActive) {
    // Check if speech synthesis is not active
    let lang = "es-US";
    let rate = 1;
    let pitch = 1.2;

    utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = lang;
    utterance.rate = rate;
    utterance.pitch = pitch;

    utterance.onstart = function () {
      isSpeechSynthesisActive = true; // Set flag to true when speech synthesis starts
    };

    utterance.onend = function () {
      isSpeechSynthesisActive = false; // Set flag to false when speech synthesis ends
    };

    window.speechSynthesis.speak(utterance);
  }
}

$(document).on(
  "click",
  '.sb-menu li[data-value="read-text"]',
  function (event) {
    event.stopPropagation();
    let dataId = $(this).closest(".sb-shadow-conversation").data("id");
    let textToRead = $(
      '.sb-shadow-conversation[data-id="' + dataId + '"] .sb-message'
    ).text();

    readText(textToRead); // Call readText function with the extracted text
  }
);

// Event listener for beforeunload event
window.addEventListener("beforeunload", function (event) {
  window.speechSynthesis.cancel();
  isSpeechSynthesisActive = false; // Reset flag on page unload
});

$(document).on("click", function (event) {
  window.speechSynthesis.cancel();
  isSpeechSynthesisActive = false; // Reset flag on document click
});

