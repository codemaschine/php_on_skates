<h1>Listing ___model_readable_name_pl___</h1>

<table>
  <thead>
    <tr>___listing_header_rows___
      <th colspan="3"></th>
    </tr>
  </thead>

  <tbody>
    <?php foreach ($___model_var_name_pl___ as $___model_var_name___): ?>
    <tr>___listing_body_rows___
      <td><?= link_to('Show', $___model_var_name___); ?></td>
      <td><?= link_to('Edit', ['action' => 'edit', 'id' => $___model_var_name___->get_id()]); ?></td>
      <td><?= link_to('Destroy', ['action' => 'destroy', 'id' => $___model_var_name___->get_id()], ['onclick' => "return confirm('Are you sure?');"]); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<br>

<?= link_to('New ___model_readable_name___', 'new'); ?>