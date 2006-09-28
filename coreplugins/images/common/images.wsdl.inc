      <!-- images -->

      <complexType name="ImagesRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="mainmap" type="types:Image"/>
          <element name="keymap" type="types:Image"/>
          <element name="scalebar" type="types:Image"/>
        </all>
      </complexType>

      <complexType name="ImagesResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="mainmap" type="types:Image"/>
          <element name="keymap" type="types:Image"/>
          <element name="scalebar" type="types:Image"/>
        </all>
      </complexType>

      <complexType name="Image">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="isDrawn" type="xsd:boolean"/>
          <element name="path" type="xsd:string"/>
          <element name="width" type="xsd:int"/>
          <element name="height" type="xsd:int"/>
          <element name="angle" type="xsd:double"/>
        </all>
      </complexType>
