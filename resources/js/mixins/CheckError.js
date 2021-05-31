export default {
  methods: {
    checkManualFillError(error) {
      console.error("post error", error);
      this.$swal(
        "Error Detected!",
        "Contact Support: "+ error,
        "error"
      );
    },
    checkDeleteError(error) {
      console.error("delete error", error);
      this.$swal(
        "Error Detected!",
        "Contact Support: "+ error,
        "error"
      );
    },
    checkCancelRequestError(error) {
      console.error("delete error", error);
      this.$swal(
        "Error Detected!",
        "Contact Support: "+error,
        "error"
      );
    },
    checkDuplicateError(error) {
      const systemMessage =
        error.response && error.response.data && error.response.data.message;
      if (systemMessage && systemMessage.includes("Duplicate entry")) {
        this.$swal(
          `An Account with this email address already exists. Please try using a different email`
        );
      } else {
        console.error("submit error", error);
        this.$swal(
          "Error Detected!",
          "Contact Support: "+error,
          "error"
        );
      }
    },
    checkOrderError(error) {
      const systemMessage =
        error.response && error.response.data && error.response.data.message;
      if (systemMessage && systemMessage.includes("cannot be null")) {
        const field = systemMessage.match(/'([^']+)'/)[1];
        this.$swal(
          `When creating an order ${field} cannot be null. Please try creating the order again.`
        );
      } else {
        console.error("submit error", error);
        this.$swal(
          "Error Detected!",
          "Contact Support: "+error,
          "error"
        );
      }
    },
  },
};
