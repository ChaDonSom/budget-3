/**
 * 
 *  Note: with Laravel Vite having vite serve assets from :3000, we can't use most solutions
 *  to serve this worker. Instead it has to be `npm run build`ed. I've found it much faster
 *  and easier to simply edit the file in `/public` and refresh, then copy over to
 *  the `resources/ts` version when finished developing.
 */

onmessage = function(event) {
  switch (event.data?.type) {
    case 'SORT_ACCOUNTS':
      let accounts = JSON.parse(event.data.accounts)
      let sort = JSON.parse(event.data.sort)

      let result = accounts.slice()
      function applySort(name) {
        result = sort[name]?.value != "none"
          ? sort[name]?.value == "ascending"
            ? result.sort((a, b) => (a[name] > b[name] ? 1 : -1))
            : result.sort((a, b) => (a[name] > b[name] ? -1 : 1))
          : result;
      }
      Object.keys(sort)
        .sort((a, b) => sort[a]?.at == sort[b]?.at ? 0 : sort[a]?.at < sort[b]?.at ? 1 : -1)
        .forEach(applySort);

      postMessage({
        type: 'SORT_ACCOUNTS',
        accounts: JSON.stringify(result)
      })
      break;
    default:
      console.log('default')
      // worker.postMessage('success')
      break;
  }
}